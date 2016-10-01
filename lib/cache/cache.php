<?php

/**
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Cache;

use Horde_Imap_Client_Cache_Backend;
use Horde_Imap_Client_Exception;
use InvalidArgumentException;

/**
 * Class UserCache
 *
 * This class is inspired by Horde_Imap_Client_Cache_Backend_Cache of the Horde Project
 *
 * @package OCA\Mail\Cache
 */
class Cache extends Horde_Imap_Client_Cache_Backend
{
	/** Cache structure version. */
	const VERSION = 3;

	/**
	 * The cache object.
	 *
	 * @var \OCP\ICache
	 */
	protected $_cache;

	/**
	 * The working data for the current pageload.  All changes take place to
	 * this data.
	 *
	 * @var array
	 */
	protected $_data = array();

	/**
	 * The list of cache slices loaded.
	 *
	 * @var array
	 */
	protected $_loaded = array();

	/**
	 * The mapping of UIDs to slices.
	 *
	 * @var array
	 */
	protected $_slicemap = array();

	/**
	 * The list of items to update:
	 *   - add: (array) List of IDs that were added.
	 *   - slice: (array) List of slices that were modified.
	 *   - slicemap: (boolean) Was slicemap info changed?
	 *
	 * @var array
	 */
	protected $_update = array();

	/**
	 * Constructor.
	 *
	 * @param array $params  Configuration parameters:
	 */
	public function __construct(array $params = array())
	{
		// Default parameters.
		$params = array_merge(array(
			'lifetime' => 604800,
			'slicesize' => 50
		), array_filter($params));

		if (!isset($params['cacheob'])) {
			throw new InvalidArgumentException('Missing cacheob parameter.');
		}

		foreach (array('lifetime', 'slicesize') as $val) {
			$params[$val] = intval($params[$val]);
		}

		parent::__construct($params);
	}

	/**
	 * Initialization tasks.
	 */
	protected function _initOb()
	{
		$this->_cache = $this->_params['cacheob'];
		register_shutdown_function(array($this, 'save'));
	}

	/**
	 * Updates the cache.
	 */
	public function save()
	{
		$lifetime = $this->_params['lifetime'];

		foreach ($this->_update as $mbox => $val) {
			$s = &$this->_slicemap[$mbox];

			if (!empty($val['add'])) {
				if ($s['c'] <= $this->_params['slicesize']) {
					$val['slice'][] = $s['i'];
					$this->_loadSlice($mbox, $s['i']);
				}
				$val['slicemap'] = true;

				foreach (array_keys(array_flip($val['add'])) as $uid) {
					if ($s['c']++ > $this->_params['slicesize']) {
						$s['c'] = 0;
						$val['slice'][] = ++$s['i'];
						$this->_loadSlice($mbox, $s['i']);
					}
					$s['s'][$uid] = $s['i'];
				}
			}

			if (!empty($val['slice'])) {
				$d = &$this->_data[$mbox];
				$val['slicemap'] = true;

				foreach (array_keys(array_flip($val['slice'])) as $slice) {
					$data = array();
					foreach (array_keys($s['s'], $slice) as $uid) {
						$data[$uid] = is_array($d[$uid])
							? serialize($d[$uid])
							: $d[$uid];
					}
					$this->_cache->set($this->_getCid($mbox, $slice), serialize($data), $lifetime);
				}
			}

			if (!empty($val['slicemap'])) {
				$this->_cache->set($this->_getCid($mbox, 'slicemap'), serialize($s), $lifetime);
			}
		}

		$this->_update = array();
	}

	/** {@inheritDoc} */
	public function get($mailbox, $uids, $fields, $uidvalid)
	{
		$ret = array();
		$this->_loadUids($mailbox, $uids, $uidvalid);

		if (empty($this->_data[$mailbox])) {
			return $ret;
		}

		if (!empty($fields)) {
			$fields = array_flip($fields);
		}
		$ptr = &$this->_data[$mailbox];

		foreach (array_intersect($uids, array_keys($ptr)) as $val) {
			if (is_string($ptr[$val])) {
				$ptr[$val] = @unserialize($ptr[$val]);
			}

			$ret[$val] = (empty($fields) || empty($ptr[$val]))
				? $ptr[$val]
				: array_intersect_key($ptr[$val], $fields);
		}

		return $ret;
	}

	/** {@inheritDoc} */
	public function getCachedUids($mailbox, $uidvalid)
	{
		$this->_loadSliceMap($mailbox, $uidvalid);
		return array_unique(array_merge(
			array_keys($this->_slicemap[$mailbox]['s']),
			(isset($this->_update[$mailbox]) ? $this->_update[$mailbox]['add'] : array())
		));
	}

	/** {@inheritDoc} */
	public function set($mailbox, $data, $uidvalid)
	{
		$update = array_keys($data);

		try {
			$this->_loadUids($mailbox, $update, $uidvalid);
		} catch (Horde_Imap_Client_Exception $e) {
			// Ignore invalidity - just start building the new cache
		}

		$d = &$this->_data[$mailbox];
		$s = &$this->_slicemap[$mailbox]['s'];
		$add = $updated = array();

		foreach ($data as $k => $v) {
			if (isset($d[$k])) {
				if (is_string($d[$k])) {
					$d[$k] = @unserialize($d[$k]);
				}
				$d[$k] = is_array($d[$k])
					? array_merge($d[$k], $v)
					: $v;
				if (isset($s[$k])) {
					$updated[$s[$k]] = true;
				}
			} else {
				$d[$k] = $v;
				$add[] = $k;
			}
		}

		$this->_toUpdate($mailbox, 'add', $add);
		$this->_toUpdate($mailbox, 'slice', array_keys($updated));
	}

	/** {@inheritDoc} */
	public function getMetaData($mailbox, $uidvalid, $entries)
	{
		$this->_loadSliceMap($mailbox, $uidvalid);

		return empty($entries)
			? $this->_slicemap[$mailbox]['d']
			: array_intersect_key($this->_slicemap[$mailbox]['d'], array_flip($entries));
	}

	/** {@inheritDoc} */
	public function setMetaData($mailbox, $data)
	{
		$this->_loadSliceMap($mailbox, isset($data['uidvalid']) ? $data['uidvalid'] : null);
		$this->_slicemap[$mailbox]['d'] = array_merge($this->_slicemap[$mailbox]['d'], $data);
		$this->_toUpdate($mailbox, 'slicemap', true);
	}

	/** {@inheritDoc} */
	public function deleteMsgs($mailbox, $uids)
	{
		$this->_loadSliceMap($mailbox);

		$slicemap = &$this->_slicemap[$mailbox];
		$deleted = array_intersect_key($slicemap['s'], array_flip($uids));

		if (isset($this->_update[$mailbox])) {
			$this->_update[$mailbox]['add'] = array_diff(
				$this->_update[$mailbox]['add'],
				$uids
			);
		}

		if (empty($deleted)) {
			return;
		}

		$this->_loadUids($mailbox, array_keys($deleted));
		$d = &$this->_data[$mailbox];

		foreach (array_keys($deleted) as $id) {
			unset($d[$id], $slicemap['s'][$id]);
		}

		foreach (array_unique($deleted) as $slice) {
			/* Get rid of slice if less than 10% of capacity. */
			if (($slice !== $slicemap['i']) &&
				($slice_uids = array_keys($slicemap['s'], $slice)) &&
				($this->_params['slicesize'] * 0.1) > count($slice_uids)) {
				$this->_toUpdate($mailbox, 'add', $slice_uids);
				$this->_cache->remove($this->_getCid($mailbox, $slice));
				foreach ($slice_uids as $val) {
					unset($slicemap['s'][$val]);
				}
			} else {
				$this->_toUpdate($mailbox, 'slice', array($slice));
			}
		}
	}

	/** {@inheritDoc} */
	public function deleteMailbox($mailbox)
	{
		$this->_loadSliceMap($mailbox);
		$this->_deleteMailbox($mailbox);
	}

	/** {@inheritDoc} */
	public function clear($lifetime)
	{
		$this->_cache->clear();
		$this->_data = $this->_loaded = $this->_slicemap = $this->_update = array();
	}

	/**
	 * Create the unique ID used to store the data in the cache.
	 *
	 * @param string $mailbox  The mailbox to cache.
	 * @param string $slice    The cache slice.
	 *
	 * @return string  The cache ID.
	 */
	protected function _getCid($mailbox, $slice)
	{
		return implode('|', array(
			'horde_imap_client',
			$this->_params['username'],
			$mailbox,
			$this->_params['hostspec'],
			$this->_params['port'],
			$slice,
			self::VERSION
		));
	}

	/**
	 * Delete a mailbox from the cache.
	 *
	 * @param string $mbox  The mailbox to delete.
	 */
	protected function _deleteMailbox($mbox)
	{
		foreach (array_merge(array_keys(array_flip($this->_slicemap[$mbox]['s'])), array('slicemap')) as $slice) {
			$cid = $this->_getCid($mbox, $slice);
			$this->_cache->remove($cid);
			unset($this->_loaded[$cid]);
		}

		unset(
		$this->_data[$mbox],
		$this->_slicemap[$mbox],
		$this->_update[$mbox]
		);
	}

	/**
	 * Load UIDs by regenerating from the cache.
	 *
	 * @param string $mailbox    The mailbox to load.
	 * @param array $uids        The UIDs to load.
	 * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
	 */
	protected function _loadUids($mailbox, $uids, $uidvalid = null)
	{
		if (!isset($this->_data[$mailbox])) {
			$this->_data[$mailbox] = array();
		}

		$this->_loadSliceMap($mailbox, $uidvalid);

		if (!empty($uids)) {
			foreach (array_unique(array_intersect_key($this->_slicemap[$mailbox]['s'], array_flip($uids))) as $slice) {
				$this->_loadSlice($mailbox, $slice);
			}
		}
	}

	/**
	 * Load UIDs from a cache slice.
	 *
	 * @param string $mailbox  The mailbox to load.
	 * @param integer $slice   The slice to load.
	 */
	protected function _loadSlice($mailbox, $slice)
	{
		$cache_id = $this->_getCid($mailbox, $slice);

		if (!empty($this->_loaded[$cache_id])) {
			return;
		}

		if ((($data = $this->_cache->get($cache_id, 0)) !== false) &&
			($data = @unserialize($data)) &&
			is_array($data)) {
			$this->_data[$mailbox] += $data;
			$this->_loaded[$cache_id] = true;
		} else {
			$ptr = &$this->_slicemap[$mailbox];

			// Slice data is corrupt; remove from slicemap.
			foreach (array_keys($ptr['s'], $slice) as $val) {
				unset($ptr['s'][$val]);
			}

			if ($slice === $ptr['i']) {
				$ptr['c'] = 0;
			}
		}
	}

	/**
	 * Load the slicemap for a given mailbox.  The slicemap contains
	 * the uidvalidity information, the UIDs->slice lookup table, and any
	 * metadata that needs to be saved for the mailbox.
	 *
	 * @param string $mailbox    The mailbox.
	 * @param integer $uidvalid  The IMAP uidvalidity value of the mailbox.
	 */
	protected function _loadSliceMap($mailbox, $uidvalid = null)
	{
		if (!isset($this->_slicemap[$mailbox]) &&
			(($data = $this->_cache->get($this->_getCid($mailbox, 'slicemap'), 0)) !== false) &&
			($slice = @unserialize($data)) &&
			is_array($slice)) {
			$this->_slicemap[$mailbox] = $slice;
		}

		if (isset($this->_slicemap[$mailbox])) {
			$ptr = &$this->_slicemap[$mailbox];
			if (is_null($ptr['d']['uidvalid'])) {
				$ptr['d']['uidvalid'] = $uidvalid;
				return;
			} elseif (!is_null($uidvalid) &&
				($ptr['d']['uidvalid'] !== $uidvalid)) {
				$this->_deleteMailbox($mailbox);
			} else {
				return;
			}
		}

		$this->_slicemap[$mailbox] = array(
			// Tracking count for purposes of determining slices
			'c' => 0,
			// Metadata storage
			// By default includes UIDVALIDITY of mailbox.
			'd' => array('uidvalid' => $uidvalid),
			// The ID of the last slice.
			'i' => 0,
			// The slice list.
			's' => array()
		);
	}

	/**
	 * Add update entry for a mailbox.
	 *
	 * @param string $mailbox  The mailbox.
	 * @param string $type     'add', 'slice', or 'slicemap'.
	 * @param mixed $data      The data to update.
	 */
	protected function _toUpdate($mailbox, $type, $data)
	{
		if (!isset($this->_update[$mailbox])) {
			$this->_update[$mailbox] = array(
				'add' => array(),
				'slice' => array()
			);
		}

		$this->_update[$mailbox][$type] = ($type === 'slicemap')
			? $data
			: array_merge($this->_update[$mailbox][$type], $data);
	}

	/* Serializable methods. */

	/**
	 */
	public function serialize()
	{
		$this->save();
		return parent::serialize();
	}

}

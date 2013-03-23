{{ script('vendor/angular/angular', 'appframework') }}
{{ script('public/app', 'appframework') }}
{{ script('mail') }}

{{ style('mail') }}

<div id="leftcontent" class="leftcontent">
	<div id="mail-folders">
		<img class="loading" src="{{ image_path('loading.gif') }}" />
	</div>
</div>
<div id="rightcontent" class="rightcontent">
	<form id="new-message" >
		<input type="button" id="mail_new_message" value="{{ trans('New Message') }}" style="display: none">
		<div id="new-message-fields" style="display: none">
			<input type="text" name="to" id="to" placeholder="<?php p($l->t('To')); ?>"/>
			<input type="text" name="subject" id="subject" placeholder="<?php p($l->t('Subject')); ?>"/>
			<textarea name="body" id="new-message-body" ></textarea>
			<input id="new-message-send" class="send" type="submit" value="<?php p($l->t('Send')) ?>">
		</div>
	</form>

	<img class="loading" id="messages-loading" src="{{ image_path('loading.gif') }}" />

	<table id="mail_messages">
		<tr class="template mail_message_summary" data-message-id="0">
			<td class="mail_message_summary_from"></td>
			<td class="mail_message_summary_subject"></td>
			<td class="mail_message_summary_date"></td>
		</tr>
		<tr class="template_loading mail_message_loading">
			<td></td>
			<td>
				<img src="{{ image_path('loading.gif') }}" />
			</td>
			<td></td>
		</tr>
	</table>
</div>

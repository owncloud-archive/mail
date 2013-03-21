{{ script('vendor/angular/angular', 'appframework') }}
{{ script('public/app', 'appframework') }}
{{ script('mail') }}

{{ style('mail') }}

<div id="mail"
	ng-app="Mail"
	ng-controller="MailAccountController">

	<form id="mail-setup" action="mailaccount/create" method="post">
	<fieldset>
		<legend>{{legend}}</legend>

		<p class="infield grouptop">
			<input type="text" name="mail-address" id="mail-address" placeholder=""
				   value="" autofocus autocomplete="off" required/>
			<label for="mail-address" class="infield">{{mailAddress}}</label>
		</p>

		<p class="infield groupbottom">
			<input type="password" name="mail-password" id="mail-password" value="" placeholder="" />
			<label for="mail-password" class="infield">{{imapPassword}}</label>
		</p>
		<img id="connect-loading" src="{{ image_path('loading.gif') }}" style="display:none;" />
		<input type="submit" id="mail_mailaccount_create" class="connect primary" value="{{connect}}"/>
	</fieldset>
</form>
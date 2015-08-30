<script id="mail-messages-template" type="text/x-handlebars-template">
	<div class="mail_message_summary {{#if flags.unseen}}unseen{{/if}} {{#if active}}active{{/if}}" data-message-id="{{id}}">
		{{#if accountMail}}
		<div class="mail-message-account-color" style="background-color: {{accountColor accountMail}}"></div>
		{{/if}}
		<div class="mail-message-header">
			<div class="sender-image avatardiv">
				{{#if senderImage}}
				<img src="{{senderImage}}" width="32px" height="32px" />
				{{else}}
				<div class="avatar" data-user="{{from}}" data-size="32"></div>
				{{/if}}
			</div>

			{{#if flags.flagged}}
			<div class="star icon-starred" data-starred="true"></div>
			{{else}}
			<div class="star icon-star" data-starred="false"></div>
			{{/if}}

			{{#if flags.answered}}
			<div class="icon-reply"></div>
			{{/if}}

			{{#if flags.hasAttachments}}
			<div class="icon-public icon-attachment"></div>
			{{/if}}

			<div class="mail_message_summary_from" title="{{fromEmail}}">{{from}}</div>
			<div class="mail_message_summary_subject" title="{{subject}}">
				{{subject}}
			</div>
			<div class="date">
					<span class="modified"
						title="{{formatDate dateInt}}">
						{{relativeModifiedDate dateInt}}
					</span>
			</div>
			<div class="icon-delete action delete" title="{{delete}}"></div>
		</div>
	</div>
</script>
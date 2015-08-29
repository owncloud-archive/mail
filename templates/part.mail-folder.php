<script id="mail-folder-template" type="text/x-handlebars-template">
	<li data-folder_id="{{id}}" data-no_select="{{noSelect}}"
		class="
		{{#if unseen}}unread{{/if}}
		{{#if active}} active{{/if}}
		{{#if specialRole}} special-{{specialRole}}{{/if}}
		{{#if folders}} collapsible{{/if}}
		{{#if open}} open{{/if}}
		">
		{{#if folders}}<button class="collapse"></button>{{/if}}
		<a class="folder {{#if specialRole}} icon-{{specialRole}} svg{{/if}}">
			{{name}}
			{{#if unseen}}
			<span class="utils">{{unseen}}</span>
			{{/if}}
		</a>
		<ul>
			{{#each folders}}
			<li data-folder_id="{{id}}"
				class="
		{{#if unseen}}unread{{/if}}
		{{#if specialRole}} special-{{specialRole}}{{/if}}
		">
				<a class="folder
					{{#if specialRole}} icon-{{specialRole}} svg{{/if}}">
					{{name}}
					{{#if unseen}}
					<span class="utils">{{unseen}}</span>
					{{/if}}
				</a>
				{{/each}}
		</ul>
	</li>
</script>
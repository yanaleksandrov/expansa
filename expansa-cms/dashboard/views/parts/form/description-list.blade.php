<?php
/**
 * Description list.
 */
defined('EX_PATH') || exit;
?>
<div class="description-list">
	<h5 class="description-title">{{ t('Menu Item Data') }}</h5>
	<dl class="description-items">
		<dt><i class="ph ph-text-aa"></i>{{ t('Menu label') }}</dt>
		<dd>
			<input type="text" name="label" value="" placeholder="{{ t('Menu label') }}">
		</dd>

		<dt><i class="ph ph-code"></i>{{ t('CSS classes') }}</dt>
		<dd>
			<input type="text" name="css_class" value="" placeholder="{{ t('CSS classes (e.g. highlight external-link)') }}">
		</dd>

		<dt><i class="ph ph-text-a-underline"></i>{{ t('Description') }}</dt>
		<dd>
			<input type="text" name="description" value="" placeholder="{{ t('Description...') }}">
		</dd>

		<dt><i class="ph ph-anchor-simple"></i>{{ t('Title attribute') }}</dt>
		<dd>
			<input type="text" name="title_attribute" value="" placeholder="{{ t('Title attribute...') }}">
		</dd>

		<dt><i class="ph ph-shapes"></i>{{ t('Icon') }}</dt>
		<dd>
			<input type="text" name="icon" value="" placeholder="{{ t('Icon class or name') }}">
		</dd>

		<dt><i class="ph ph-crosshair-simple"></i>{{ t('Open in') }}</dt>
		<dd>
			<select name="target" u-select>
				<option value="_self">{{ t('Same tab or window') }}</option>
				<option value="_blank">{{ t('New tab or window') }}</option>
				<option value="_parent">{{ t('Parent frame') }}</option>
				<option value="_top">{{ t('Full window') }}</option>
			</select>
		</dd>

		<dt><i class="ph ph-eye"></i>{{ t('Visibility') }}</dt>
		<dd>
			<select name="visibility" u-select>
				<option value="all">{{ t('Everyone') }}</option>
				<option value="guest">{{ t('Guests only') }}</option>
				<option value="registered">{{ t('Logged-in users') }}</option>
				<option value="admin">{{ t('Administrators only') }}</option>
				<option value="hidden">{{ t('Hidden') }}</option>
			</select>
		</dd>

		<dt><i class="ph ph-google-logo"></i>{{ t('SEO link relations') }}</dt>
		<dd>
			<select name="rel[]" u-select multiple>
				<option value="nofollow">{{ t('Nofollow') }}</option>
				<option value="sponsored">{{ t('Sponsored link') }}</option>
				<option value="ugc">{{ t('User-generated content') }}</option>
				<option value="noopener">{{ t('No opener access') }}</option>
				<option value="noreferrer">{{ t('Do not send referrer') }}</option>
			</select>
		</dd>

		<dt><i class="ph ph-handshake"></i>{{ t('Relations XFN') }}</dt>
		<dd>
			<select name="xfn[]" u-select multiple>
				<optgroup label="{{ t('Friendship') }}">
					<option value="friend">{{ t('Friend') }}</option>
					<option value="acquaintance">{{ t('Acquaintance') }}</option>
					<option value="contact">{{ t('Contact') }}</option>
				</optgroup>
				<optgroup label="{{ t('Physical') }}">
					<option value="met">{{ t('Met in person') }}</option>
				</optgroup>
				<optgroup label="{{ t('Professional') }}">
					<option value="co-worker">{{ t('Co-worker') }}</option>
				</optgroup>
				<optgroup label="{{ t('Geographical') }}">
					<option value="co-resident">{{ t('Co-resident') }}</option>
				</optgroup>
				<optgroup label="{{ t('Family') }}">
					<option value="child">{{ t('Child') }}</option>
					<option value="parent">{{ t('Parent') }}</option>
					<option value="sibling">{{ t('Sibling') }}</option>
					<option value="spouse">{{ t('Spouse') }}</option>
					<option value="kin">{{ t('Relative') }}</option>
				</optgroup>
				<optgroup label="{{ t('Romantic') }}">
					<option value="muse">{{ t('Muse') }}</option>
					<option value="crush">{{ t('Crush') }}</option>
					<option value="date">{{ t('Date') }}</option>
					<option value="sweetheart">{{ t('Sweetheart') }}</option>
				</optgroup>
				<optgroup label="{{ t('Identity') }}">
					<option value="me">{{ t('My own site or profile') }}</option>
				</optgroup>
			</select>
		</dd>
	</dl>
</div>
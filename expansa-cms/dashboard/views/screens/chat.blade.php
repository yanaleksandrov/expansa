<?php
/**
 * AI assistant chat template can be overridden by copying it to themes/yourtheme/dashboard/views/screens/chat.php
 *
 * @package Expansa\Templates
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<?php
$labels = [
	'newProcess'    => t_attr( 'New Process' ),
	'queued'        => t_attr( 'Waiting for the worker to start' ),
	'seconds'       => t_attr( '%s s' ),
	'tokens'        => t_attr( '%s tokens' ),
	'ask'           => t_attr( 'Describe the feature you need.' ),
	'answer'        => t_attr( 'Answer the questions.' ),
	'network'       => t_attr( 'The server did not respond. Check the connection and try again.' ),
	'notConfigured' => t_attr( 'The AI service is not configured: set it on the AI tab of the settings.' ),
];
?>
<div class="chat" u-data="chat" data-labels="{{ json_encode( $labels, JSON_UNESCAPED_UNICODE ) }}" @load="init($el)">
	<section class="chat-main">
		<div class="chat-progress" u-bind="progressBar">
			<div class="chat-progress-bar"></div>
		</div>

		<div class="chat-messages">
			<div class="chat-empty" u-bind="emptyState">
				<?php
				echo view(
                    'components/state',
					[
						'icon'        => 'ufo',
						'title'       => t('Start of the process'),
						'description' => t('Ask a question to continue this process.'),
					]
				)->render();
				?>
			</div>

			<div class="chat-message" u-each="(message, i) in messages" u-bind="messageItem(message)">
				<div class="chat-message-avatar">
					<i u-bind="messageAvatar(message)"></i>
				</div>
				<div class="chat-message-body">
					<div class="chat-step-head" u-bind="stepHead(message)">
						<span class="chat-step-label" u-bind="stepLabel(message)"></span>
						<span class="chat-step-meta" u-bind="stepMeta(message)"></span>
					</div>
					<div class="chat-message-text" u-bind="messageText(message)"></div>
					<ol class="chat-message-list" u-bind="listOf(message, 'questions')">
						<li u-each="(item, j) in message.questions" u-bind="lineText(item)"></li>
					</ol>
					<ul class="chat-message-list chat-message-list--muted" u-bind="listOf(message, 'details')">
						<li u-each="(item, j) in message.details" u-bind="lineText(item)"></li>
					</ul>
					<ul class="chat-message-list chat-message-list--errors" u-bind="listOf(message, 'errors')">
						<li u-each="(item, j) in message.errors" u-bind="lineText(item)"></li>
					</ul>
					<details class="chat-message-spoiler" u-bind="specification(message)">
						<summary><?php echo t( 'Specification' ); ?></summary>
						<div class="chat-message-spoiler-body" u-bind="specificationText(message)"></div>
					</details>
					<div class="chat-message-files" u-bind="listOf(message, 'files')">
						<details class="chat-message-spoiler" u-each="(file, j) in message.files">
							<summary u-bind="fileTitle(file)"></summary>
							<pre class="chat-message-code"><code u-bind="fileContent(file)"></code></pre>
						</details>
					</div>
					<div class="chat-message-time" u-bind="messageTime(message)"></div>
				</div>
			</div>
		</div>

		<div class="chat-thinking" u-bind="thinkingIndicator">
			<div class="chat-message-avatar">
				<i class="ph ph-sparkle"></i>
			</div>
			<div class="chat-thinking-body">
				<div class="chat-thinking-dots"><span></span><span></span><span></span></div>
				<div class="chat-thinking-status" u-bind="thinkingText"></div>
			</div>
		</div>

		<div class="chat-notice" u-bind="notice"></div>

		<div class="chat-composer">
			<textarea class="chat-composer-input" rows="1" u-bind="composerInput"></textarea>
			<button type="button" class="btn btn--primary chat-composer-send" u-bind="sendButton">
				<i class="ph ph-paper-plane-tilt"></i>
			</button>
			<button type="button" class="btn btn--outline chat-composer-stop" u-bind="stopButton">
				<i class="ph ph-stop"></i>
			</button>
		</div>
	</section>

	<aside class="chat-sidebar">
		<button type="button" class="btn btn--primary chat-sidebar-new" u-bind="newProcessButton">
			<i class="ph ph-plus"></i> <?php echo t( 'New Process' ); ?>
		</button>

		<div class="chat-sidebar-title"><?php echo t( 'Processes' ); ?></div>

		<div class="chat-sidebar-list">
			<div class="chat-sidebar-item" u-each="(process, i) in tasks" u-bind="processRow(process)">
				<span class="chat-sidebar-item-status">
					<span class="chat-sidebar-item-status-label" u-bind="processStatusLabel(process)"></span>
					<span class="chat-sidebar-item-status-badge" u-bind="processStatusBadge(process)">
						<i u-bind="processStatusGlyph(process)"></i>
					</span>
				</span>

				<button type="button" class="chat-sidebar-item-main" u-bind="processSelectButton(process)">
					<span class="chat-sidebar-item-title" u-bind="processTitle(process)"></span>
				</button>

				<input type="text" class="chat-sidebar-item-rename" u-bind="processRenameInput(process)">

				<details class="details chat-sidebar-item-menu" @click.outside="$el.removeAttribute('open')">
					<summary class="btn btn--icon btn--xs chat-sidebar-item-menu-btn" title="<?php echo t_attr( 'Actions' ); ?>">
						<i class="ph ph-dots-three-vertical"></i>
					</summary>
					<div class="details-content">
						<ul class="user-menu">
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="renameProcessButton(process)">
									<i class="ph ph-pencil-simple"></i> <?php echo t( 'Rename' ); ?>
								</button>
							</li>
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="archiveProcessButton(process)">
									<i class="ph ph-archive"></i> <?php echo t( 'Archive' ); ?>
								</button>
							</li>
							<li class="user-menu-item">
								<button type="button" class="user-menu-link" u-bind="deleteProcessButton(process)">
									<i class="ph ph-trash"></i> <?php echo t( 'Delete' ); ?>
								</button>
							</li>
						</ul>
					</div>
				</details>
			</div>
		</div>
	</aside>
</div>

<?php
/**
 * Chat.
 *
 * This template can be overridden by copying it to themes/yourtheme/dashboard/views/chat.php
 *
 * @package Expansa\Templates
 * @since   2025.1
 */
if ( ! defined( 'EX_PATH' ) ) {
	exit;
}
?>
<div class="expansa-main">
	<div class="chat">
		<div class="chat__users">
			<div class="dg g-6">
				<div class="dg g-1">
					<div class="fw-500 fs-16">All chats</div>
					<input type="search" placeholder="Search user">
				</div>
				<!-- tab start -->
				<div class="tab" x-data="tab('open')">
					<div class="tab__nav">
						<span class="tab__title" :class="{ 'active': tab === 'open' }" @click="tab = 'open'">
							<i class="ph ph-chats"></i> Open chats
						</span>
						<span class="tab__title" :class="{ 'active': tab === 'archive' }" @click="tab = 'archive'">
							<i class="ph ph-archive-box"></i> Archive
						</span>
					</div>
					<div class="tab__content dg g-6" x-show="tab === 'open'">
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=3)">
								<i class="badge badge--green" title="Online"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">John Doe <span class="fs-13 t-muted">15:32</span></div>
								<div class="fs-13 t-green">Typing...</div>
							</div>
						</div>
						<div class="chat__user df">
							<div class="avatar avatar--rounded">
								EJ
								<i class="badge badge--muted" title="Offline"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">Elizabeth Jenson <span class="fs-13 t-muted">11:11</span></div>
								<div class="fs-13 t-muted">Well, talk about design ideas 🔥</div>
							</div>
						</div>
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=39)">
								<i class="badge badge--muted" title="Offline"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">Emmie F. Batalonger <span class="fs-13 t-muted">14:45</span></div>
								<div class="fs-13 t-muted">Do not make this anymore</div>
							</div>
						</div>
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150)">
								<i class="badge badge--muted" title="Offline"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">John Smith <span class="fs-13 t-muted">17:50</span></div>
								<div class="fs-13 t-muted">How can I be a happy?</div>
							</div>
						</div>
					</div>
					<div class="tab__content dg g-6" x-show="tab === 'archive'">
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=4)">
								<i class="badge badge--green" title="Online"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">John Doe <span class="fs-13 t-muted">15:32</span></div>
								<div class="fs-13 t-green">Typing...</div>
							</div>
						</div>
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=7)">
								<i class="badge badge--muted" title="Offline"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">Emmie F. Batalonger <span class="fs-13 t-muted">14:45</span></div>
								<div class="fs-13 t-muted">Do not make this anymore</div>
							</div>
						</div>
						<div class="chat__user df">
							<div class="avatar avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=17)">
								<i class="badge badge--muted" title="Offline"></i>
							</div>
							<div class="ml-4">
								<div class="df aib jcsb fw-500 fs-15 mb-1">John Smith <span class="fs-13 t-muted">17:50</span></div>
								<div class="fs-13 t-muted">How can I be a happy?</div>
							</div>
						</div>
					</div>
				</div>
				<!-- tab end -->
			</div>
		</div>
		<div class="chat__main">
			<div class="chat__messages" x-init="$el.scrollTop = $el.scrollHeight;">
				<div class="chat__message">
					<div class="chat__comment">Hey, Emmie.</div>
					<div class="chat__date">11:56 am</div>
				</div>
				<div class="chat__message">
					<div class="chat__comment">Can we got a quick call?</div>
					<div class="chat__date">11:56 am</div>
				</div>
				<div class="chat__message nth">
					<div class="chat__comment">Hey, Jakob. Sure, but now I'm busy rn.</div>
					<div class="chat__date">11:58 am</div>
				</div>
				<div class="chat__message">
					<div class="chat__comment">Okay, no problem. I will briefly describe the situation. I am trying to connect the application to your system, but it throws an error.</div>
					<div class="chat__date">12:03 pm</div>
				</div>
				<div class="chat__message">
					<div class="chat__comment">Maybe I did something wrong.</div>
					<div class="chat__date">12:05 pm</div>
				</div>
				<div class="chat__message nth">
					<div class="chat__comment">Jakob, I see that you have successfully connected. The problem is that you need to specify the activation key in the settings. Then everything will work :)</div>
					<div class="chat__date">12:33 pm</div>
				</div>
				<div class="chat__message">
					<div class="chat__comment">Yes, you're right. Thanks for the help.</div>
					<div class="chat__date">12:47 pm</div>
				</div>
				<div class="chat__message nth">
					<div class="chat__comment">Your welcome 😊👊</div>
					<div class="chat__date">12:49 pm</div>
				</div>
			</div>
			<form class="chat__form" action="" method="post">
				<textarea name="comment" placeholder="Type something..." x-textarea></textarea>
				<input type="hidden" name="user_id" value="1">
				<button class="btn btn--primary icon-send" type="submit"></button>
			</form>
		</div>
		<div class="chat__meta">
			<div class="dg g-8">
				<div class="dg g-1">
					<div class="df fdc aic">
						<div class="avatar avatar--xl avatar--rounded" style="background-image: url(https://i.pravatar.cc/150?img=39)">
							<i class="badge badge--muted" title="Offline"></i>
						</div>
						<div class="mt-2 fs-13 t-muted">chat with</div>
						<div class="fw-500 fs-16">Emmie F. Batalonger</div>
						<div class="mt-2 fs-13 t-center t-muted">
							I like using my previous Online Optimization skills to help other authors with the 'technical' stuff.
						</div>
					</div>
				</div>
				<div class="dg g-1">
					<div class="df aic">
						<span class="t-muted"><i class="ph ph-clock-afternoon"></i> local time</span> <span class="ml-auto">11:59 pm</span>
					</div>
					<div class="df aic">
						<span class="t-muted"><i class="ph ph-user"></i> role</span> <span class="ml-auto">moderator</span>
					</div>
					<div class="df aic">
						<span class="t-muted"><i class="ph ph-map-pin"></i> location</span> <span class="ml-auto">Moscow</span>
					</div>
				</div>
				<div class="dg g-1">
					<div class="fs-13 t-muted">Search in dialog</div>
					<input type="search" name="search" placeholder="e.g. image.png">
				</div>
				<div class="dg g-3">
					<div class="df aib">
						<span class="fs-16 fw-500">Files</span><span class="ml-auto">See all</span>
					</div>
					<div class="df aic">
						<div class="avatar">
							<i class="ph ph-file-zip"></i>
						</div>
						<div class="ml-3 df fdc">
							<div class="fs-15">References.zip</div>
							<div class="fs-13 t-muted">Oct 21, 2022 at 21:07</div>
						</div>
					</div>
					<div class="df aic">
						<div class="avatar">
							<i class="ph ph-file-csv"></i>
						</div>
						<div class="ml-3 df fdc">
							<div class="fs-15">Products.csv</div>
							<div class="fs-13 t-muted">Sep 13, 2022 at 13:56</div>
						</div>
					</div>
					<div class="df aic">
						<div class="avatar">
							<i class="ph ph-folder-notch-open"></i>
						</div>
						<div class="ml-3 df fdc">
							<div class="fs-15">Mockups.psd</div>
							<div class="fs-13 t-muted">Sep 13, 2022 at 13:56</div>
						</div>
					</div>
					<div class="df aic">
						<div class="avatar">
							<i class="ph ph-folder-notch-open"></i>
						</div>
						<div class="ml-3 df fdc">
							<div class="fs-15">Mockups.psd</div>
							<div class="fs-13 t-muted">Sep 13, 2022 at 13:56</div>
						</div>
					</div>
					<div class="df aic">
						<div class="avatar">
							<i class="ph ph-folder-notch-open"></i>
						</div>
						<div class="ml-3 df fdc">
							<div class="fs-15">Mockups.psd</div>
							<div class="fs-13 t-muted">Sep 13, 2022 at 13:56</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

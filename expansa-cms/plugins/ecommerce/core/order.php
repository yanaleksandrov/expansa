<?php
use Expansa\I18n;

/**
 * Order editor form.
 *
 * @since 2025.1
 */
return Dashboard\Form::enqueue(
	'order-editor',
	[
		'class' => 'dg g-3 p-5 pt-4',
	],
	[
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-2',
			'fields'        => [
				[
					'type'        => 'select',
					'name'        => 'customer',
					'label'       => I18n::_t( 'Customer' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'value'    => $user->locale ?? '',
						'x-select' => '{"showSearch": 1}',
					],
					'options'     => [
						'select' => I18n::_t( 'Ian Aleksandrov' ),
					],
				],
				[
					'type'        => 'datetime-local',
					'name'        => 'datetime',
					'label'       => I18n::_t( 'Date created' ),
					'class'       => '',
					'label_class' => '',
					'reset'       => 0,
					'before'      => '',
					'after'       => '',
					'instruction' => '',
					'tooltip'     => '',
					'copy'        => 0,
					'validator'   => '',
					'conditions'  => [],
					'attributes'  => [
						'required' => 1,
					],
				],
				[
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="dg g-1">
							<div class="df g-2 aic jcsb fw-600">Contact information</div>
							<ul>
								<li>yanalexandrov@gmail.com</li>
								<li>No phone number</li>
							</ul>
						</div>
						<?php
					},
				],
			],
		],
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-2',
			'fields'        => [
				[
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="dg g-2">
							<div class="df g-2 aic jcsb fw-600">
								Shipping address <span class="btn btn--sm btn--icon t-muted"><i class="ph ph-pen"></i></span>
							</div>
							<ul class="dg g-2 lh-sm">
								<li class="df aib g-2"><i class="ph ph-person-simple-run"></i> Ian Aleksandrov</li>
								<li class="df aib g-2"><i class="ph ph-buildings"></i> Company Name</li>
								<li class="df aib g-2"><i class="ph ph-flag-banner-fold"></i> Turkiye, Antalya, Kepez, 07210</li>
								<li class="df aib g-2"><i class="ph ph-mailbox"></i> Karşıyaka Mahallesi Semt Parkı and verylongaddressname</li>
								<li class="df aib g-2"><i class="ph ph-phone"></i> <a href="tel:+90122342353">+90122342353</a></li>
							</ul>
						</div>
						<?php
					},
				],
				[
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="dg g-1">
							<div class="df g-2 aic jcsb fw-600">
								Billing address <span class="btn btn--sm btn--icon t-muted"><i class="ph ph-pen"></i></span>
							</div>
							<ul>
								<li>Same as shipping address</li>
							</ul>
						</div>
						<?php
					},
				],
				[
					'type'          => 'group',
					'name'          => 'values-group',
					'label'         => '',
					'class'         => 'dg ga-2',
					'label_class'   => '',
					'content_class' => 'dg g-3 gtc-2',
					'fields'        => [
						[
							'type'        => 'text',
							'name'        => 'firstname',
							'label'       => I18n::_t( 'First Name' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'lastname',
							'label'       => I18n::_t( 'Last Name' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'company',
							'label'       => I18n::_t( 'Company Name' ),
							'class'       => 'field ga-2',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '<i class="ph ph-buildings"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'address',
							'label'       => I18n::_t( 'Phone' ),
							'class'       => 'field ga-2',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '<i class="ph ph-phone"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'select',
							'name'        => 'country',
							'label'       => I18n::_t( 'Country / Region' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '<i class="ph ph-flag-banner-fold"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => $user->locale ?? '',
								'x-select' => '{"showSearch": 1}',
							],
							'options'     => [
								'select' => I18n::_t( 'United States' ),
							],
						],
						[
							'type'        => 'select',
							'name'        => 'country',
							'label'       => I18n::_t( 'State / County' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'value'    => $user->locale ?? '',
								'x-select' => '{"showSearch": 1}',
							],
							'options'     => [
								'select' => I18n::_t( 'United States' ),
							],
						],
						[
							'type'        => 'text',
							'name'        => 'city',
							'label'       => I18n::_t( 'City' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '<i class="ph ph-city"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'zip',
							'label'       => I18n::_t( 'Postal code / ZIP' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '<i class="ph ph-mailbox"></i>',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'address',
							'label'       => I18n::_t( 'Address' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'text',
							'name'        => 'address',
							'label'       => I18n::_t( 'Apartment, suite, etc' ),
							'class'       => '',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [],
						],
						[
							'type'        => 'textarea',
							'name'        => 'note',
							'label'       => I18n::_t( 'Customer provided note:' ),
							'class'       => 'field ga-2',
							'label_class' => 'df aic fs-12 t-muted',
							'reset'       => 0,
							'before'      => '',
							'after'       => '',
							'instruction' => '',
							'tooltip'     => '',
							'copy'        => 0,
							'validator'   => '',
							'conditions'  => [],
							'attributes'  => [
								'placeholder' => I18n::_t( 'Customer notes about the order' ),
							],
						],
					],
				],
			],
		],
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-1',
			'fields'        => [
				[
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="product">
							<div class="product-title">Products</div>
							<div class="product-action">
								<input class="product-table-input" type="search" name="s" placeholder="Search products">
								<button type="button" class="btn">Add custom item</button>
							</div>
							<div class="product-table">
								<div class="product-table-row">
									<div class="product-table-col">Product</div>
									<div class="product-table-col">Quantity</div>
									<div class="product-table-col">Total</div>
									<div class="product-table-col"></div>
								</div>
								<div class="product-table-row">
									<div class="product-table-col">
										<div class="product-table-item">
											<img class="product-table-image" src="https://cdn.shopify.com/s/files/1/0703/7560/4260/files/avatar_40x40@3x.jpg?v=1734901120" width="48" height="48" alt="Product Name">
											<ul class="product-table-data">
												<li><a href="#" target="_blank"><strong>Test product</strong></a></li>
												<li>$0.00</li>
												<li>Custom description of the product</li>
												<li>Blue, Red, 2XL</li>
											</ul>
										</div>
									</div>
									<div class="product-table-col">
										<input class="product-table-input" type="number" name="quantity" value="1" min="1" max="9999">
									</div>
									<div class="product-table-col">$9999.00</div>
									<div class="product-table-col">
										<button type="button" class="btn btn--xs btn--icon"><i class="ph ph-x"></i></button>
									</div>
								</div>
							</div>
						</div>
						<?php
					},
				],
			],
		],
		[
			'type'          => 'group',
			'name'          => 'values-group',
			'label'         => '',
			'class'         => 'dg attributes-form',
			'label_class'   => '',
			'content_class' => 'dg g-3 gtc-1',
			'fields'        => [
				[
					'type'     => 'custom',
					'callback' => function() {
						?>
						<div class="payment">
							<div class="payment-title">
								Payment
								<span class="payment-label"><span class="badge badge--sm badge--green-lt">Paid: $550.45</span> on December 23, 10:14 pm</span>
							</div>
							<div class="payment-table">
								<div class="payment-table-row">
									<div class="payment-table-col"><strong>Subtotal</strong></div>
									<div class="payment-table-col">1 product</div>
									<div class="payment-table-col"><strong>$0.00</strong></div>
								</div>
								<div class="payment-table-row">
									<div class="payment-table-col"><a href="#">Add discount</a></div>
									<div class="payment-table-col">—</div>
									<div class="payment-table-col">$0.00</div>
								</div>
								<div class="payment-table-row">
									<div class="payment-table-col"><a href="#">Add shipping or delivery</a></div>
									<div class="payment-table-col">—</div>
									<div class="payment-table-col">$0.00</div>
								</div>
								<div class="payment-table-row">
									<div class="payment-table-col"><a href="#">Estimated tax</a></div>
									<div class="payment-table-col">Not calculated</div>
									<div class="payment-table-col">$0.00</div>
								</div>
								<div class="payment-table-row">
									<div class="payment-table-col"><strong>Total</strong></div>
									<div class="payment-table-col"></div>
									<div class="payment-table-col"><strong>$0.00</strong></div>
								</div>
							</div>
							<div class="payment-action">
								<button type="button" class="btn">Send invoice</button>
								<button type="button" class="btn">Mark as paid</button>
							</div>
						</div>
						<?php
					},
				],
			],
		],
	]
);
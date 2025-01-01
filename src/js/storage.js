import Selection from './storage/selection';

document.addEventListener( 'alpine:init', () => {

	/**
	 * Storage functions
	 *
	 * @since 1.0
	 */
	Alpine.directive('storage', (el, {value, expression, modifiers}, {evaluateLater, cleanup}) => {
		new Selection({
			container: el,
			selector: '.storage__item',
			classSelected: 'active',
			onSelectEnd: selection => {
				console.log(selection)
			}
		});
	});
});
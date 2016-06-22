// Default autoload code for Slimbox2
// AUTOLOAD CODE BLOCK (MAY BE CHANGED OR REMOVED)
if (!/android|iphone|ipod|series60|symbian|windows ce|blackberry/i.test(navigator.userAgent)) {
	jQuery(function($) {
		$("a[rel^='slimbox']").slimbox({/* Put custom options here */}, null, function(el) {
			return (this == el) || ((this.rel.length > 7) && (this.rel == el.rel));
		});
	});
}

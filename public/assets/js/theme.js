// Theme toggle - self-hosted (no CDN, no inline: strict CSP forbids both).
// Progressive enhancement: with JS off the toggle is inert and the page simply
// follows the OS via prefers-color-scheme. With JS on, one click flips the
// <html data-theme> attribute AND persists a cookie so the SERVER renders the
// same attribute on the next load -> no flash of the wrong theme.
(function () {
	'use strict';

	var COOKIE = 'theme';
	var root = document.documentElement;

	function currentTheme() {
		// explicit choice wins; otherwise resolve what the OS is showing now
		if (root.dataset.theme) return root.dataset.theme;
		return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
	}

	function apply(theme) {
		root.dataset.theme = theme;
		// 1 year, path=/ so every zone sees it, Lax matches the session cookie policy
		document.cookie = COOKIE + '=' + theme + ';path=/;max-age=31536000;samesite=Lax';
	}

	document.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-theme-toggle]');
		if (!btn) return;
		e.preventDefault();
		apply(currentTheme() === 'dark' ? 'light' : 'dark');
	});
})();

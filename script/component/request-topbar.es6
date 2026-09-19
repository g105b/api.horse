document.querySelectorAll("request-topbar").forEach(component => {
	let mobileMenu = component.querySelector("#mobileMenu");
	let mobileMenuToggle = component.querySelector("#mobileMenuToggle");
	let shareModal = component.querySelector("#shareModal");
	let shareButton = component.querySelector("button.share");
	let copyButton = component.querySelector(".copy-share-link");

	// The menu arrives open in the document so collection controls remain usable
	// without JavaScript. Enhancement turns it into the mobile drawer.
	if(mobileMenu && typeof mobileMenu.showModal === "function") {
		mobileMenu.close();
		mobileMenuToggle.hidden = false;
		mobileMenuToggle.addEventListener("click", () => mobileMenu.showModal());
	}

	component.querySelectorAll("[data-dialog-open]").forEach(button => {
		button.addEventListener("click", () => {
			document.getElementById(button.dataset.dialogOpen)?.showModal();
		});
	});

	shareButton.addEventListener("click", event => {
		let shareLinkInput = shareModal.querySelector("[name='shareLink']");

		if(!canShareNatively(shareLinkInput.value)) {
			return;
		}

		event.preventDefault();
		event.stopImmediatePropagation();

		shareNatively(shareLinkInput.value).catch(error => {
			if(error.name === "AbortError") {
				return;
			}

			shareModal.showModal();
		});
	}, {capture: true});

	shareModal.addEventListener("toggle", () => {

		if(shareModal.open) {
			shareModal.querySelector("[name='shareLink']").select();
		}
	});

	copyButton.addEventListener("click", e => {
		e.preventDefault();
		let shareLinkInput = shareModal.querySelector("[name='shareLink']");
		shareLinkInput.select();
		if(navigator.clipboard?.writeText) {
			navigator.clipboard.writeText(shareLinkInput.value).then(() => shareModal.close());
		}
	});
});

function canShareNatively(url) {
	if(typeof navigator.share !== "function") {
		return false;
	}

	if(typeof navigator.canShare !== "function") {
		return true;
	}

	return navigator.canShare({url});
}

function shareNatively(url) {
	return navigator.share({url});
}

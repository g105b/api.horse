document.querySelectorAll("request-topbar").forEach(component => {
	let shareModal = component.querySelector("#shareModal");
	let shareButton = component.querySelector("button.share");
	let copyButton = component.querySelector(".copy-share-link");

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
		navigator.clipboard.writeText(shareLinkInput.value);
		shareModal.close();
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

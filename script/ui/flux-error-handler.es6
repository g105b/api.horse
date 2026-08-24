document.addEventListener("flux:before-render", event => {
	let updates = event.detail?.updates;
	if(!Array.isArray(updates)) {
		return;
	}

	let responseDocument = updates.find(update => update.newElement)
		?.newElement.ownerDocument;
	let errorHeading = responseDocument?.querySelector("main > h1");
	if(errorHeading?.textContent.trim() !== "Error 500") {
		return;
	}

	let errorMessage = responseDocument
		.querySelector("main > details > summary h2")
		?.textContent.trim();

	// Emptying the batch stops Flux from replacing the current page with the
	// error document once this event handler returns.
	updates.length = 0;
	alert(errorMessage || "Something went wrong");
});

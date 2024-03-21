document.querySelectorAll("request-editor").forEach(component => {
	if(window.controlReturnCallback === undefined) {
		window.controlReturnCallback = controlReturnCallback;
		window.addEventListener("keypress", controlReturnCallback);
	}
});

function controlReturnCallback(e) {
	if(e.key !== "Enter") {
		return;
	}
	if(!e.ctrlKey) {
		return;
	}

	console.log(e);
	let details = e.target.closest("details");
	if(!details) {
		return;
	}

	let addButton = details.querySelector("form.add button");
	if(!addButton) {
		return;
	}

	e.preventDefault();
	addButton.click();
}

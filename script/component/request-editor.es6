document.querySelectorAll("request-editor").forEach(component => {
	console.log("REQUEST-EDITOR!");

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

	let container = e.target.closest("div.option,details");
	if(!container) {
		e.target.blur();
		return;
	}

	e.target.blur();

	let addButton = container.querySelector("form.add button");
	if(!addButton) {
		return;
	}

	e.preventDefault();
	addButton.click();
	// setTimeout(() => {
	// }, 1000)
}

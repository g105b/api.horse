document.querySelectorAll("request-editor").forEach(component => {
	console.log("REQUEST-EDITOR!");

	if(window.controlReturnCallback === undefined) {
		window.controlReturnCallback = controlReturnCallback;
		window.addEventListener("keypress", controlReturnCallback);
	}

	component.querySelectorAll("form.actions.primary").forEach(form => {
		console.log("Adding submit event to form", form);

		form.addEventListener("submit", e => {
			form.classList.add("submitting");

			let button = form.querySelector("button");
			if(!button) {
				return;
			}

			button.dataset["originalText"] = button.textContent;
			button.textContent = "Sending...";
		});
	});
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

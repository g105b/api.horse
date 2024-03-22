document.querySelectorAll("request-editor").forEach(component => {
	if(window.controlReturnCallback === undefined) {
		window.controlReturnCallback = controlReturnCallback;
		window.addEventListener("keypress", controlReturnCallback);
	}

	component.querySelectorAll("form.actions.primary").forEach(form => {
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

	let addButton = container.querySelector("form.add button");
	if(!addButton) {
		e.target.blur();
		return;
	}

	e.preventDefault();
	addButton.click();
}

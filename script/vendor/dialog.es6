document.querySelectorAll("[data-confirm]").forEach(initConfirm);

function initConfirm(element) {
	let messageText = element.dataset["confirm"];
	element.addEventListener("click", async(e) => {
		if(element.dataset["confirmClicked"]) {
			return;
		}

		e.preventDefault();

		const result = await confirm(messageText, e);
		if(result) {
			element.dataset["confirmClicked"] = "true";
			console.log(element);
			element.click();
		}
	});
}

function confirm(messageText) {

	return new Promise(resolve => {
		let dialog = document.createElement("dialog");
		dialog.dataset["dialog"] = "confirm";
		let form = document.createElement("form");
		form.method = "dialog";
		let message = document.createElement("p");
		message.textContent = messageText;
		let okButton = document.createElement("button");
		okButton.textContent = "OK";
		okButton.value = "ok";
		okButton.classList.add("positive");
		okButton.autofocus = true;
		let cancelButton = document.createElement("button");
		cancelButton.textContent = "Cancel";

		let actions = document.createElement("div");
		actions.style.display = "flex";
		actions.style.justifyContent = "space-between";
		actions.append(okButton, cancelButton);
		form.append(message, actions);
		dialog.append(form);
		document.body.append(dialog);

		dialog.showModal();
		dialog.addEventListener("close", function() {
			resolve(dialog.returnValue);
		});
	});
}

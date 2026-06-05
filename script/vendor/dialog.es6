document.querySelectorAll("dialog[data-dialog='auto-modal'][open]:not([data-dialog='confirm'])").forEach(openAsModal);
document.querySelectorAll("[data-confirm]").forEach(initConfirm);

const observer = new MutationObserver(mutations => {
	for(const mutation of mutations) {
		for(const node of mutation.addedNodes) {
			if(!(node instanceof HTMLElement)) {
				continue;
			}

			if(node instanceof HTMLDialogElement && node.open && !node.matches("[data-dialog='confirm']")) {
				openAsModal(node);
			}

			if(node.matches("[data-confirm]")) {
				initConfirm(node);
			}
			node.querySelectorAll?.("[data-confirm]").forEach(initConfirm);
		}
	}
});

observer.observe(document.body, {
	childList: true,
	subtree: true,
});

function openAsModal(dialog) {
	console.log("OAM:", dialog);
	if(dialog.open) {
		dialog.close();
		dialog.showModal();
	}
}


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
		actions.classList.add("actions");
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

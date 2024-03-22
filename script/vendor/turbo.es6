/*
# `data-turbo="autosave"`
On buttons, this will visually hide the button, but automatically "click" it
every time its containing form changes.
 */
const DEBUG = true;

const parser = new DOMParser();
let turboElementList = document.querySelectorAll("[data-turbo]");
let updateElementCollection = {};
DEBUG && console.debug("Turbo element list:", turboElementList);
let turboStyle = document.createElement("style");
document.head.append(turboStyle);
turboStyle.id = "turbo-style";
turboStyle.innerHTML = `
.turbo-hidden {
	display: none;
}
`;

window.addEventListener("popstate", function(e) {
	location.href = document.location;
});

let elementEventMap = new Map();
let addEventListenerOriginal = EventTarget.prototype.addEventListener;
Element.prototype.addEventListener = function addEventListenerTurbo(type, listener) {
	let element = this;
	let mapObj = elementEventMap.has(element)
		? elementEventMap.get(element)
		: {};

	if(mapObj[type] === undefined) {
		mapObj[type] = [];
	}
	mapObj[type].push(listener);
	elementEventMap.set(element, mapObj);

	addEventListenerOriginal(type, listener);
	DEBUG && console.log(`Event ${type} added to element:`, element);
};

turboElementList.forEach(init);
console.log(elementEventMap);

function init(turboElement) {
	let turboType = turboElement.dataset["turbo"];
	turboElement.classList.add("turbo-active");
	delete turboElement.dataset["turbo"];

	if(turboType === "autosave") {
		initAutoSave(turboElement)
	}
	else if(turboType === "update" || turboType === "update-inner" || turboType === "update-outer") {
		let updateType = null;
		if(turboType === "update" || turboType === "update-outer") {
			updateType = "outer";
		}
		else if(turboType === "update-inner") {
			updateType = "inner";
		}
		storeUpdateElement(turboElement, updateType);
	}
	else {
		console.error(`Unknown turbo element type: ${turboType}`, turboElement);
	}
}

function storeUpdateElement(element, updateType) {
	if(!updateType) {
		updateType = "_none";
	}

	if(updateElementCollection[updateType] === undefined) {
		updateElementCollection[updateType] = [];
	}

	updateElementCollection[updateType].push(element);
	console.log(`Pushing into ${updateType}: `, element);
}

function processUpdateElements(newDocument) {
	let autofocusElement = newDocument.querySelector("[autofocus]");
	if(autofocusElement) {
		autofocusElement.classList.add("turbo-autofocus");
	}
	for(let type of Object.keys(updateElementCollection)) {
		updateElementCollection[type].forEach(function(existingElement) {
			if(!existingElement) {
				return;
			}

			let activeElement = null;
			let activeElementSelection = null;
			if(existingElement.contains(document.activeElement)) {
				activeElement = getXPathForElement(document.activeElement);
				activeElementSelection = [];
				if(document.activeElement.selectionStart && document.activeElement.selectionEnd) {
					activeElementSelection.push(document.activeElement.selectionStart, document.activeElement.selectionEnd);
				}
			}
			let xPath = getXPathForElement(existingElement, document);
			let xPathResult = newDocument.evaluate(xPath, newDocument.documentElement);
			let newElement = xPathResult.iterateNext();

			if(type === "outer") {
				let existingElementIndex = updateElementCollection[type].indexOf(existingElement);
				updateElementCollection[type][existingElementIndex] = newElement;
				if(newElement) {
					reattachEventListeners(existingElement, newElement);
					reattachTurboElements(existingElement, newElement);
					existingElement.replaceWith(newElement);
				}
			}
			else if(type === "inner") {
				reattachEventListeners(existingElement, newElement);
				reattachTurboElements(existingElement, newElement);

				while(existingElement.firstChild) {
					existingElement.removeChild(existingElement.firstChild);
				}
				while(newElement && newElement.firstChild) {
					existingElement.appendChild(newElement.firstChild);
				}
			}

			if(activeElement) {
				console.log("Active element", activeElement);
				let elementToActivate = document.evaluate(activeElement, document.documentElement).iterateNext();
				if(elementToActivate) {
					console.log("Element to activate", elementToActivate, activeElementSelection);
					elementToActivate.focus();
					if(elementToActivate.setSelectionRange) {
						elementToActivate.setSelectionRange(activeElementSelection[0], activeElementSelection[1]);
					}

					let completeClickFunction = () => {
						elementToActivate.removeEventListener("mouseup", completeClickFunction);

						setTimeout(() => {
							console.log("Completing click", elementToActivate);
							elementToActivate.click();
						}, 10)
					};
					addEventListenerOriginal.call(elementToActivate, "mouseup", completeClickFunction);
				}
			}
		});
	}

	document.querySelectorAll(".turbo-autofocus").forEach(autofocusElement => {
		autofocusElement.focus();
	});
}

function initAutoSave(turboElement) {
	if(!turboElement instanceof HTMLButtonElement) {
		console.error("TurboElement type autosave must be an HTMLButtonElement", turboElement);
		return;
	}

	if(!turboElement.form) {
		console.error("TurboElement type autosave must be within a form", turboElement)
		return;
	}

	turboElement.classList.add("turbo-hidden", "turbo-autosave");

	if(!turboElement.form.turboObj) {
		turboElement.form.turboObj = {};
	}
	turboElement.form.turboObj = {
		autoSave: {
			key: turboElement.name,
			value: turboElement.value,
		}
	};
	turboElement.form.classList.add("turbo-has-obj");
	turboElement.form.addEventListener("change", formChangeAutoSave);
	turboElement.form.addEventListener("submit", formSubmitAutoSave);
}

function formChangeAutoSave(e) {
	let form = e.target;
	if(form.form instanceof HTMLFormElement) {
		form = form.form;
	}

	submitForm(form, completeAutoSave);
}

function formSubmitAutoSave(e) {
	e.preventDefault();
	document.activeElement.blur();
	let form = e.target;
	if(form.form instanceof HTMLFormElement) {
		form = form.form;
	}
	let submitter = null;
	if(e.submitter instanceof HTMLButtonElement) {
		submitter = e.submitter;
	}
	submitForm(form, completeAutoSave, submitter);
}

function completeAutoSave(newDocument) {
// The setTimeout with 0 delay doesn't mean it would execute immediately, it
// schedules the execution immediately after the running script to strive to
// execute as soon as possible. This is also known as yielding to the browser.
// It's necessary to allow for click events to be processed before updating the
// DOM mid-click and causing clicks to be missed on children of updated elements.
	setTimeout(() => {
		processUpdateElements(newDocument);
	}, 0);
}

function submitForm(form, callback, submitter) {
	let formData = getFormDataForButton(form, "autoSave", submitter);

	fetch(form.action, {
		method: form.getAttribute("method"),
		credentials: "same-origin",
		body: formData,
	}).then(response => {
		if(!response.ok) {
			console.error("Form submission error", response);
			return;
		}

		history.pushState({
			"action": "submitForm"
		}, "", response.url);
		return response.text();
	}).then(html => {
		callback(parser.parseFromString(html, "text/html"));
	});
}

function getFormDataForButton(form, type, submitter) {
	let formData = new FormData(form);
	if(submitter) {
		formData.set(submitter.name, submitter.value);
	}
	else if(form.turboObj[type]) {
		formData.set(form.turboObj[type].key, form.turboObj[type].value);
	}
	return formData;
}

/** This was adapted from https://developer.mozilla.org/en-US/docs/Web/XPath/Snippets */
function getXPathForElement(element, context) {
	let xpath = "";
	if(context instanceof Document) {
		context = context.documentElement;
	}
	if(!context) {
		context = element.ownerDocument.documentElement;
	}

	while(element !== context) {
		let pos = 0;
		let sibling = element;
		while(sibling) {
			if(sibling.nodeName === element.nodeName) {
				pos += 1;
			}
			sibling = sibling.previousElementSibling;
		}

		xpath = `./${element.nodeName}[${pos}]/${xpath}`;
		element = element.parentElement;
	}
	xpath = xpath.replace(/\/$/, "");
	return xpath;
}

function reattachEventListeners(oldElement, newElement) {
	if(!elementEventMap.has(oldElement)) {
		return;
	}

	let mapObj = elementEventMap.get(oldElement);
	for(let type of Object.keys(mapObj)) {
		for(let listener of mapObj[type]) {
			console.log("Listener for element:", oldElement, listener);
		}
	}
}

function reattachTurboElements(oldElement, newElement) {
	if(!newElement) {
		return;
	}

	newElement.querySelectorAll("[data-turbo]").forEach(init);
	oldElement.querySelectorAll(".turbo-has-obj").forEach(turboElement => {
		let xPath = getXPathForElement(turboElement, oldElement);
		let newTurboElement = newElement.ownerDocument.evaluate(xPath, newElement).iterateNext();
		if(newTurboElement) {
			newTurboElement.turboObj = turboElement.turboObj;
			newTurboElement.classList.add("turbo-has-obj");
		}
	});
}

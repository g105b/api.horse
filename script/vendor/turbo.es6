/*
# `data-turbo="autosave"`
On buttons, this will visually hide the button, but automatically "click" it
every time its containing form changes.
 */
const DEBUG = true;

DEBUG && console.group("TURBO!");

const parser = new DOMParser();
let turboElementList = document.querySelectorAll("[data-turbo]");
let updateElementCollection = {};
DEBUG && console.debug("Turbo element list:", turboElementList);
let turboStyle = document.createElement("style");
document.head.append(turboStyle);
turboStyle.id = "turbo-style";
turboStyle.innerHTML = `
[data-turbo="autosave"] {
	display: none;
}
`;

window.addEventListener("popstate", function(e) {
	location.href = document.location;
});

let elementEventMap = new Map();
let addEventListenerOriginal = EventTarget.prototype.addEventListener;
Element.prototype.addEventListener = function addEventListenerTurbo(type, listener, options) {
	let element = this;
	let mapObj = elementEventMap.has(element)
		? elementEventMap.get(element)
		: {};

	if(mapObj[type] === undefined) {
		mapObj[type] = [];
	}
// TODO: Do we need to store the "options" in here as a tuple?
	mapObj[type].push(listener);
	elementEventMap.set(element, mapObj);

	addEventListenerOriginal.call(this, type, listener, options);
	DEBUG && console.debug(`Event ${type} added to element:`, element);
};
DEBUG && console.debug("Hooked addEventListener", Element.prototype.addEventListener);

turboElementList.forEach(init);

function init(turboElement) {
	let turboType = turboElement.dataset["turbo"];

	if(turboType === "autosave") {
		initAutoSave(turboElement);
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
	else if(turboType === "submit") {
		initAutoSubmit(turboElement);
	}
	else {
		console.error(`Unknown turbo element type: ${turboType}`, turboElement);
	}
}

/**
 * The updateElementCollection array is a list of all elements that require
 * updating when the document updates. When something happens that requires the
 * document to update, the processUpdateElements function will iterate over all
 * of these stored updateElements and update their content accordingly.
 */
function storeUpdateElement(element, updateType) {
	if(!updateType) {
		updateType = "_none";
	}

	if(updateElementCollection[updateType] === undefined) {
		updateElementCollection[updateType] = [];
	}

	updateElementCollection[updateType].push(element);

	DEBUG && console.debug("storeUpdateElement completed", `Pushing into ${updateType}: `, element);
}

/**
 * The updateElementCollection array is a list of all elements that require
 * updating when the document updates. This function is triggered whenever the
 * document's data changes, so the updateElements can be swapped out from the
 * old document with the new document's counterparts.
 */
function processUpdateElements(newDocument) {
	let autofocusElement = newDocument.querySelector("[autofocus]");
	if(autofocusElement) {
		autofocusElement.dataset["turboAutofocus"] = "";
	}
	for(let type of Object.keys(updateElementCollection)) {
		updateElementCollection[type].forEach(function(existingElement) {
			if(!existingElement) {
				return;
			}

			let activeElement = null;
			let activeElementSelection = null;
			let activeElementValue = null;
			if(existingElement.contains(document.activeElement)) {
				activeElement = getXPathForElement(document.activeElement);
				activeElementSelection = [];
				activeElementValue = document.activeElement.value;
				if(document.activeElement.selectionStart >= 0 && document.activeElement.selectionEnd >= 0) {
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
				DEBUG && console.debug("Active element", activeElement);
				let elementToActivate = document.evaluate(activeElement, document.documentElement).iterateNext();
				if(elementToActivate) {
					DEBUG && console.debug("Element to activate", elementToActivate, activeElementSelection);
					elementToActivate.focus();
					elementToActivate.value = activeElementValue;
					if(elementToActivate.setSelectionRange) {
						elementToActivate.setSelectionRange(activeElementSelection[0], activeElementSelection[1]);
					}

					let completeClickFunction = () => {
						elementToActivate.removeEventListener("mouseup", completeClickFunction);

						setTimeout(() => {
							DEBUG && console.debug("Completing click", elementToActivate);
							elementToActivate.click();
						}, 10)
					};
					addEventListenerOriginal.call(elementToActivate, "mouseup", completeClickFunction);
				}
			}
		});
	}

	document.querySelectorAll("[data-turbo-autofocus]").forEach(autofocusElement => {
		autofocusElement.focus();
	});
}

function initAutoSave(turboElement) {
	if(!(turboElement instanceof HTMLButtonElement)) {
		console.error("TurboElement type autosave must be an HTMLButtonElement", turboElement);
		return;
	}

	if(!turboElement.form) {
		console.error("TurboElement type autosave must be within a form", turboElement)
		return;
	}

	if(!turboElement.form.turboObj) {
		turboElement.form.turboObj = {};
	}
	turboElement.form.turboObj = {
		autoSave: {
			key: turboElement.name,
			value: turboElement.value,
		}
	};
	// turboElement.form.classList.add("turbo-has-obj");
	turboElement.form.dataset["turboObj"] = "";
	turboElement.form.addEventListener("change", formChangeAutoSave);
	turboElement.form.addEventListener("submit", formSubmitAutoSave);

	DEBUG && console.debug("initAutoSave completed", turboElement);
}

function initAutoSubmit(button) {
	let form = button.form;
	if(!form) {
		return;
	}

	form.addEventListener("submit", autoSubmit);
}

function autoSubmit(e) {
	e.preventDefault();
	setTimeout(() => {
		submitForm(e.target, completeAutoSave, e.submitter);
	}, 0);
}

function formChangeAutoSave(e) {
	let form = e.target;
	if(form.form instanceof HTMLFormElement) {
		let element = form;
		element.classList.add("input-changed");
		(function(c_element) {
			setTimeout(function(){
				c_element.classList.remove("input-changed");
			}, 100);
		})(element);

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

	let recentlyChangedInput = form.querySelector(".input-changed");
	if(recentlyChangedInput) {
		return;
	}

	let submitter = null;
	if(e.submitter instanceof HTMLButtonElement) {
		submitter = e.submitter;
	}
	submitForm(form, completeAutoSave, submitter);
}

function completeAutoSave(newDocument) {
	if(newDocument.head.children.length === 0) {
		console.error("Error processing new document!");
		location.reload();
	}
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
	form.classList.add("submitting");

	fetch(form.action, {
		method: form.getAttribute("method"),
		credentials: "same-origin",
		body: formData,
	}).then(response => {
		form.classList.remove("submitting");

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
			DEBUG && console.debug("Listener for element:", oldElement, listener);
		}
	}
}

function reattachTurboElements(oldElement, newElement) {
	if(!newElement) {
		return;
	}

	newElement.querySelectorAll("[data-turbo]").forEach(init);
	oldElement.querySelectorAll("[data-turbo-obj]").forEach(turboElement => {
		let xPath = getXPathForElement(turboElement, oldElement);
		let newTurboElement = newElement.ownerDocument.evaluate(xPath, newElement).iterateNext();
		if(newTurboElement) {
			newTurboElement.turboObj = turboElement.turboObj;
			newTurboElement.dataset["turboObj"] = "";
		}
	});
}

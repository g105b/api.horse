<?php
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;

class FeatureContext extends MinkContext {
	/** @Given I fill in the :component input :inputName with :value */
	public function iFillInTheInputWith(string $componentName, string $inputName, string $value):void {
		$component = $this->getSession()->getPage()->find("css", $componentName);
		if(!$component) {
			throw new ExpectationException(
				"Could not find component '$componentName'.",
				$this->getSession()->getDriver()
			);
		}

		$field = $component->find("css", "[name='$inputName']");
		if(!$field) {
			throw new ExpectationException(
				"Could not find input '$inputName' in '$componentName'.",
				$this->getSession()->getDriver()
			);
		}

		$field->click();
		$field->setValue($value);
		$this->focusElement($field);
	}

	/** @Then I should see :content in the :componentName */
	public function iShouldSeeInThe(string $content, string $componentName):void {
		$this->assertSession()->elementTextContains("css", $componentName, $content);
	}

	/** @Given I submit the form */
	public function iSubmitTheForm():void {
		$result = $this->getSession()->evaluateScript(<<<'JS'
			(function() {
				const element = document.activeElement;
				if(!element) {
					return {
						submitted: false,
						message: "There is no focused element.",
					};
				}

				const form = element.form || element.closest("form");
				if(!form) {
					return {
						submitted: false,
						message: "The focused element is not inside a form.",
					};
				}

				const submitter = form.querySelector(
					'button:not([type]), button[type="submit"], ' +
					'input[type="submit"], input[type="image"]'
				);

				if(!form.checkValidity()) {
					const invalid = form.querySelector(":invalid");
					return {
						submitted: false,
						message: invalid
							? `Form validation failed on '${invalid.name || invalid.id || invalid.tagName}'.`
							: "Form validation failed.",
					};
				}

				if(form.requestSubmit) {
					form.requestSubmit(submitter || undefined);
					return { submitted: true };
				}

				if(submitter) {
					submitter.click();
					return { submitted: true };
				}

				form.submit();
				return { submitted: true };
			})()
			JS);

		if(!$result["submitted"]) {
			throw new ExpectationException(
				$result["message"],
				$this->getSession()->getDriver()
			);
		}
	}

	private function focusElement(NodeElement $element):void {
		$xpath = json_encode($element->getXpath());
		$this->getSession()->executeScript(<<<JS
			(function() {
				const element = document.evaluate(
					$xpath,
					document,
					null,
					XPathResult.FIRST_ORDERED_NODE_TYPE,
					null
				).singleNodeValue;
				if(element) {
					element.focus();
				}
			})()
			JS);
	}
}

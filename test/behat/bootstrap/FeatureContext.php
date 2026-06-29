<?php
use Behat\Behat\Tester\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\MinkContext;
use function PHPUnit\Framework\assertEquals;

class FeatureContext extends MinkContext {
	/** @Then the :component input :inputName should contain :value */
	public function theInputShouldContain(string $componentName, string $inputName, string $value):void {
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

		assertEquals($value, $field->getValue());
	}

	/** @Given I fill in the :component input :inputName with :value */
	public function iFillInTheInputWith(string $componentName, string $inputName, string $value):void {
		$this->flushActiveFluxAutoSave();

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

	public function pressButton($button):void {
		$this->flushActiveFluxAutoSave();

		$button = $this->fixStepArgument($button);
		$this->getSession()->getPage()->pressButton($button);
		$this->waitForFlux();
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

				if(form.fluxObj?.autoSave && form.querySelector('button[data-flux="autosave"]')) {
					element.blur();
					return {
						submitted: true,
						waitForFlux: true,
					};
				}

				const button = form.querySelector("button");
				if(button) {
					button.click();
					return { submitted: true };
				}

				return {
					submitted: false,
					message: "The focused element's form does not contain a button.",
				};
			})()
			JS);

		if(!$result["submitted"]) {
			throw new ExpectationException(
				$result["message"],
				$this->getSession()->getDriver()
			);
		}

		if($result["waitForFlux"] ?? false) {
			$this->waitForFlux();
		}
	}

	private function flushActiveFluxAutoSave():void {
		$result = $this->getSession()->evaluateScript(<<<'JS'
			(function() {
				const element = document.activeElement;
				const form = element?.form || element?.closest?.("form");
				if(form?.fluxObj?.autoSave && form.querySelector('button[data-flux="autosave"]')) {
					element.blur();
					return true;
				}

				return false;
			})()
			JS);

		if($result) {
			$this->waitForFlux();
		}
	}

	private function waitForFlux():void {
		$this->getSession()->wait(5000, "document.querySelector('.flux-form-waiting') === null");
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

	/** @When I press the theme toggle */
	public function iPressTheThemeToggle():void {
		$button = $this->getSession()->getPage()->find("css", "[data-theme-toggle]");
		if(!$button) {
			throw new ExpectationException(
				"Could not find the theme toggle button.",
				$this->getSession()->getDriver()
			);
		}

		$button->press();
	}

	/** @Then the colour scheme override should be :mode */
	public function theColourSchemeOverrideShouldBe(string $mode):void {
		$mode = $this->fixStepArgument($mode);
		$result = $this->getSession()->evaluateScript(<<<'JS'
			document.documentElement.dataset.theme || "system"
			JS
		);

		assertEquals($mode, $result);
	}
}

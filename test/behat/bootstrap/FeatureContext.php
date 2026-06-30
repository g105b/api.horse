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

	/** @Given I fill in the request editor :editorName input :inputName with :value */
	public function iFillInTheRequestEditorInputWith(
		string $editorName,
		string $inputName,
		string $value,
	):void {
		$this->flushActiveFluxAutoSave();

		$editor = $this->getRequestEditor($editorName);
		$this->openElement($editor);

		$field = $editor->find("css", "[name='$inputName']");
		if(!$field) {
			throw new ExpectationException(
				"Could not find input '$inputName' in request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		$field->click();
		$field->setValue($value);
		$this->focusElement($field);
	}

	/** @When I press :button in the request editor :editorName */
	public function iPressInTheRequestEditor(string $button, string $editorName):void {
		$this->flushActiveFluxAutoSave();

		$editor = $this->getRequestEditor($editorName);
		$this->openElement($editor);

		$buttonElement = $editor->findButton($button);
		if(!$buttonElement) {
			throw new ExpectationException(
				"Could not find button '$button' in request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		$buttonElement->click();
		$this->waitForFlux();
	}

	/** @Given I fill in row :rowNumber of the request editor :editorName input :inputName with :value */
	public function iFillInRowOfTheRequestEditorInputWith(
		int $rowNumber,
		string $editorName,
		string $inputName,
		string $value,
	):void {
		$this->flushActiveFluxAutoSave();

		$row = $this->getRequestEditorRow($editorName, $rowNumber);
		$field = $row->find("css", "[name='$inputName']");
		if(!$field) {
			throw new ExpectationException(
				"Could not find input '$inputName' in row $rowNumber of request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		$field->click();
		$field->setValue($value);
		$this->focusElement($field);
	}

	/** @When I press :button in row :rowNumber of the request editor :editorName */
	public function iPressInRowOfTheRequestEditor(
		string $button,
		int $rowNumber,
		string $editorName,
	):void {
		$this->flushActiveFluxAutoSave();

		$row = $this->getRequestEditorRow($editorName, $rowNumber);
		$buttonElement = $row->findButton($button);
		if(!$buttonElement) {
			throw new ExpectationException(
				"Could not find button '$button' in row $rowNumber of request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		$buttonElement->click();
		$this->waitForFlux();
	}

	/** @When I press :button in the :componentName row containing :text */
	public function iPressInTheRowContaining(
		string $button,
		string $componentName,
		string $text,
	):void {
		$this->flushActiveFluxAutoSave();

		$component = $this->getSession()->getPage()->find("css", $componentName);
		if(!$component) {
			throw new ExpectationException(
				"Could not find component '$componentName'.",
				$this->getSession()->getDriver()
			);
		}

		foreach($component->findAll("css", "li") as $row) {
			if(!str_contains($row->getText(), $text)) {
				continue;
			}

			$buttonElement = $row->findButton($button);
			if(!$buttonElement) {
				throw new ExpectationException(
					"Could not find button '$button' in '$componentName' row containing '$text'.",
					$this->getSession()->getDriver()
				);
			}

			$this->acceptConfirm();
			$buttonElement->click();
			$this->waitForFlux();
			return;
		}

		throw new ExpectationException(
			"Could not find '$componentName' row containing '$text'.",
			$this->getSession()->getDriver()
		);
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

	/** @Then I should see the secret :key with value :value */
	public function iShouldSeeTheSecretWithValue(string $key, string $value):void {
		$component = $this->getSession()->getPage()->find("css", "secrets-editor");
		if(!$component) {
			throw new ExpectationException(
				"Could not find component 'secrets-editor'.",
				$this->getSession()->getDriver()
			);
		}

		foreach($component->findAll("css", "ul.kvp > li") as $row) {
			if(str_contains($row->getText(), $key)
			&& str_contains($row->getText(), $value)) {
				return;
			}
		}

		throw new ExpectationException(
			"Could not find secret '$key' with value '$value'.",
			$this->getSession()->getDriver()
		);
	}

	/** @Then the request raw message should be: */
	public function theRequestRawMessageShouldBe(PyStringNode $expected):void {
		$field = $this->getSession()
			->getPage()
			->find("css", "request-editor textarea[name='message']");
		if(!$field) {
			throw new ExpectationException(
				"Could not find the raw request message textarea.",
				$this->getSession()->getDriver()
			);
		}

		assertEquals(
			$this->normaliseMultilineText($expected->getRaw()),
			$this->normaliseMultilineText($field->getValue()),
		);
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

	private function acceptConfirm():void {
		$this->getSession()->executeScript("window.confirm = function() { return true; }");
	}

	private function openElement(NodeElement $element):void {
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
					element.open = true;
				}
			})()
			JS);
	}

	private function getRequestEditor(string $editorName):NodeElement {
		$editor = $this->getSession()
			->getPage()
			->find("css", "request-editor [data-editor='$editorName']");
		if(!$editor) {
			throw new ExpectationException(
				"Could not find request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		return $editor;
	}

	private function getRequestEditorRow(
		string $editorName,
		int $rowNumber,
	):NodeElement {
		$editor = $this->getRequestEditor($editorName);
		$this->openElement($editor);

		$rowList = array_values(array_filter(
			$editor->findAll("css", "ul.multiple > li"),
			fn(NodeElement $row) => $row->isVisible(),
		));
		$row = $rowList[$rowNumber - 1] ?? null;
		if(!$row) {
			throw new ExpectationException(
				"Could not find row $rowNumber in request editor '$editorName'.",
				$this->getSession()->getDriver()
			);
		}

		return $row;
	}

	private function normaliseMultilineText(string $text):string {
		$text = str_replace("\r\n", "\n", $text);
		return trim($text);
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

	/** @Then the colour scheme override should be visually distinct from system */
	public function theColourSchemeOverrideShouldBeVisuallyDistinctFromSystem():void {
		$result = $this->getSession()->evaluateScript(<<<'JS'
			(() => {
				const systemTheme = window.matchMedia("(prefers-color-scheme: dark)").matches
					? "dark"
					: "light";
				return {
					actual: document.documentElement.dataset.theme || "system",
					expected: systemTheme === "light" ? "dark" : "light",
				};
			})()
			JS);

		assertEquals($result["expected"], $result["actual"]);
	}

	/** @Then the colour scheme override should visually match system */
	public function theColourSchemeOverrideShouldVisuallyMatchSystem():void {
		$result = $this->getSession()->evaluateScript(<<<'JS'
			(() => {
				const systemTheme = window.matchMedia("(prefers-color-scheme: dark)").matches
					? "dark"
					: "light";
				return {
					actual: document.documentElement.dataset.theme || "system",
					expected: systemTheme,
				};
			})()
			JS);

		assertEquals($result["expected"], $result["actual"]);
	}
}

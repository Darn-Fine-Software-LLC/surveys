<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Darn Fine Surveys</title>
	<link rel="stylesheet" href="https://cdn.simplecss.org/simple.min.css">
	<script src="//unpkg.com/alpinejs" defer></script>
</head>
<body>
	<style>
		body {
			background-color: #fcfdfc;
		}
		.question-block {
			border: 1px solid var(--border);
			border-radius: var(--standard-border-radius);
			padding: 1rem 1.25rem;
			margin-bottom: 1rem;
		}
		.question-header {
			display: flex;
			justify-content: space-between;
			align-items: center;
			margin-bottom: 0.75rem;
		}
		.question-header h5 {
			margin: 0;
		}
		.choice-row {
			display: flex;
			gap: 0.5rem;
			align-items: center;
			margin-bottom: 0.4rem;
		}
		.choice-row input {
			flex: 1;
			margin: 0;
		}
		.choice-row button {
			padding: 0.2rem 0.5rem;
			margin: 0;
		}
		.choices-section {
			margin-top: 0.75rem;
		}
		.choices-section label {
			margin-bottom: 0.25rem;
		}
		.submit-row {
			padding: 0.5rem 1rem;
			border-top: 1px solid #e2e3e3;
			border-bottom: 1px solid #e2e3e3;
			display: flex;
			justify-content: center;
			align-items: center;
			margin-top: 2rem;

			button {
				margin: 0;
			}
		}
	</style>

	<header>
		<h1>Surveys Without The Bullshit</h1>
		<p>Couple of clicks, you have a survey for free. It expires and deletes eventually. Results and responses are public if ya know the ID.</p>
	</header>
	<main>
		<section>
			<h3>It's Survey Time!</h3>
			<p>Create your survey below, you'll get a link to share it after. Remember, everything is public so don't ask for info you wouldn't want your neighbor to see!</p>
			<form action="/surveys/create.php" method="POST" x-data="surveyBuilder()" @submit="prepareSubmit">
				<label>
					<div>Survey Title</div>
					<input required type="text" name="title" />
				</label>

				<label>
					<div>Expiration</div>
					<select required name="expiration_length">
						<option value="1">1 Day</option>
						<option value="7">1 Week</option>
						<option value="31">1 Month</option>
					</select>
				</label>

				<h5>Questions</h5>
				<div id="survey-questions">
					<template x-for="(question, qi) in questions" :key="qi">
						<div class="question-block">
							<div class="question-header">
								<h6 x-text="'Question ' + (qi + 1)"></h6>
								<button type="button" @click="removeQuestion(qi)">Remove</button>
							</div>

							<label>
								<div>Label</div>
								<input required type="text"
									:name="'questions[' + qi + '][label]'"
									x-model="question.label"
									placeholder="e.g. What is your favorite color?" />
							</label>

							<label>
								<div>Description <small>(optional)</small></div>
								<input type="text"
									:name="'questions[' + qi + '][description]'"
									x-model="question.description"
									placeholder="Any extra context for this question" />
							</label>

							<label>
								<div>Type</div>
								<select :name="'questions[' + qi + '][type]'" x-model="question.type">
									<option value="radio">Radio (pick one)</option>
									<option value="checkbox">Checkbox (pick many)</option>
									<option value="select">Select (dropdown)</option>
									<option value="text_short">Short text (255 chars)</option>
									<option value="text_long">Long text (1000 chars)</option>
								</select>
							</label>

							<label style="display:flex; align-items:center; gap:0.5rem; width:auto;">
								<input type="checkbox"
									:name="'questions[' + qi + '][required]'"
									value="1"
									x-model="question.required"
									style="width:auto; margin:0;" />
								<span>Required</span>
							</label>

							<div class="choices-section" x-show="needsChoices(question.type)">
								<label><div>Answer Choices</div></label>
								<template x-for="(choice, ci) in question.choices" :key="ci">
									<div class="choice-row">
										<input required type="text"
											:name="'questions[' + qi + '][choices][' + ci + ']'"
											x-model="question.choices[ci]"
											placeholder="Choice label" />
										<button type="button" @click="removeChoice(qi, ci)"
											x-show="question.choices.length > 2">&times;</button>
									</div>
								</template>
								<button type="button" @click="addChoice(qi)">+ Add Choice</button>
							</div>
						</div>
					</template>

					<p x-show="questions.length === 0" style="color: var(--text-muted);">
						No questions yet. Add one below.
					</p>
				</div>

				<div style="display: flex; justify-content: center; width: 100%;">
					<button type="button" @click="addQuestion()">+ Add Question</button>
				</div>

				<div class="submit-row">
					<button type="submit" class="btn btn-secondary" :disabled="questions.length === 0">Create Survey</button>
				</div>
			</form>
		</section>
	</main>

	<script>
		function surveyBuilder() {
			return {
				questions: [],

				addQuestion() {
					this.questions.push({
						label: '',
						description: '',
						type: 'radio',
						required: false,
						choices: ['', '']
					});
				},

				removeQuestion(index) {
					this.questions.splice(index, 1);
				},

				addChoice(questionIndex) {
					this.questions[questionIndex].choices.push('');
				},

				removeChoice(questionIndex, choiceIndex) {
					this.questions[questionIndex].choices.splice(choiceIndex, 1);
				},

				needsChoices(type) {
					return ['radio', 'checkbox', 'select'].includes(type);
				},

				prepareSubmit(e) {
					if (this.questions.length === 0) {
						e.preventDefault();
						alert('Please add at least one question.');
					}
				}
			}
		}
	</script>
</body>
</html>

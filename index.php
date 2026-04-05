	<?php
$active_surveys  = 0;
$total_responses = 0;
$db_path = __DIR__ . '/database/database.sqlite';
if (file_exists($db_path)) {
    try {
        $db = new PDO('sqlite:' . $db_path);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $active_surveys  = (int)$db->query('SELECT COUNT(*) FROM surveys WHERE expires_at > ' . time())->fetchColumn();
        $total_responses = (int)$db->query('SELECT COUNT(*) FROM submissions')->fetchColumn();
    } catch (Exception $e) { /* db not ready yet */ }
}
?>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Darn Fine Surveys — Create a Survey</title>
	<meta name="description" content="Create a simple, no-Bull survey in seconds. Share the link, collect responses, results are public. Surveys auto-delete when they expire.">
	<meta property="og:title" content="Darn Fine Surveys — Create a Survey">
	<meta property="og:description" content="Create a simple, no-Bull survey in seconds. Share the link, collect responses, results are public. Surveys auto-delete when they expire.">
	<meta property="og:type" content="website">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Noto+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">
	<script src="//unpkg.com/alpinejs" defer></script>
	<link rel="stylesheet" type="text/css" href="/css/style.css">
</head>
<body>

	<header class="site-header">
		<h1>Surveys Without The Bull</h1>
		<p>Couple of clicks, you have a survey. It expires. Results are public — don't ask for anything you'd hide from your neighbor.</p>
	</header>

	<main>
		<div class="stats-row">
			<div class="stat-card">
				<span class="stat-number"><?= htmlspecialchars((string)$active_surveys) ?></span>
				<span class="stat-label">Active Surveys Hapening Now</span>
			</div>
			<div class="stat-card">
				<span class="stat-number"><?= htmlspecialchars((string)$total_responses) ?></span>
				<span class="stat-label">People Responding to Surveys</span>
			</div>
		</div>

		<div class="form-intro">
			<h2>Create a Survey</h2>
			<p>Fill in the details below and add your questions. You'll get a shareable link when you're done.</p>
		</div>

		<form action="/surveys/create.php" method="POST" x-data="surveyBuilder()" @submit="prepareSubmit">

			<div class="form-card">
				<div class="field">
					<label for="title">Survey Title</label>
					<input id="title" required type="text" name="title" placeholder="e.g. Team Lunch Preferences" />
				</div>
				<div class="field">
					<label for="expiration">Expiration <span class="hint">(auto-deletes after this)</span></label>
					<select id="expiration" required name="expiration_length">
						<option value="1">1 Day</option>
						<option value="7">1 Week</option>
						<option value="31">1 Month</option>
					</select>
				</div>
			</div>

			<div id="survey-questions">
				<template x-for="(question, qi) in questions" :key="qi">
					<div class="question-block">

						<div class="question-header">
							<span class="question-number" x-text="'Question ' + (qi + 1)"></span>
							<button type="button" class="btn btn-danger" @click="removeQuestion(qi)">Remove</button>
						</div>

						<div class="field">
							<label>Label</label>
							<input required type="text"
								:name="'questions[' + qi + '][label]'"
								x-model="question.label"
								placeholder="e.g. What is your favorite color?" />
						</div>

						<div class="field">
							<label>Description <span class="hint">(optional)</span></label>
							<input type="text"
								:name="'questions[' + qi + '][description]'"
								x-model="question.description"
								placeholder="Any extra context for this question" />
						</div>

						<div class="field">
							<label>Type</label>
							<select :name="'questions[' + qi + '][type]'" x-model="question.type">
								<option value="radio">Radio — pick one</option>
								<option value="checkbox">Checkbox — pick many</option>
								<option value="select">Dropdown — pick one</option>
								<option value="text_short">Short text (255 chars)</option>
								<option value="text_long">Long text (1000 chars)</option>
							</select>
						</div>

						<div class="field-check">
							<input type="checkbox"
								:name="'questions[' + qi + '][required]'"
								:id="'required_' + qi"
								value="1"
								x-model="question.required" />
							<span>Required</span>
						</div>

						<div class="choices-section" x-show="needsChoices(question.type)">
							<div class="choices-label">Answer Choices</div>
							<template x-for="(choice, ci) in question.choices" :key="ci">
								<div class="choice-row">
									<input type="text"
										:required="needsChoices(question.type)"
										:name="'questions[' + qi + '][choices][' + ci + ']'"
										x-model="question.choices[ci]"
										placeholder="Choice label" />
									<button type="button" class="btn btn-ghost"
										@click="removeChoice(qi, ci)"
										x-show="question.choices.length > 2"
										title="Remove choice">&times;</button>
								</div>
							</template>
							<button type="button" class="btn btn-ghost" @click="addChoice(qi)">+ Add choice</button>
						</div>

					</div>
				</template>

				<div class="empty-state" x-show="questions.length === 0">
					No questions yet — add one below.
				</div>
			</div>

			<div class="add-question-row">
				<button type="button" class="btn btn-secondary" @click="addQuestion()">+ Add Question</button>
			</div>

			<div class="submit-row">
				<button type="submit" class="btn btn-primary" :disabled="questions.length === 0">
					Create Survey &rarr;
				</button>
			</div>

		</form>
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

<footer class="site-footer">
	Created by <a href="https://darnfinesoftware.com">Darn Fine Software</a> in Ohio
	<span class="footer-sep">&middot;</span>
	<a href="https://github.com/Darn-Fine-Software-LLC/surveys">View source</a>
	<span class="footer-sep">&middot;</span>
	<a href="mailto:hi@thatalexguy.dev">Contact us</a>
</footer>

</body>
</html>

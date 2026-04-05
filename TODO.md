- Homepage
	- What is Surveys
		- Free, open source tool for quickly conducting surveys that auto delete after a specified timeframe.
		- All surveys are public and anyone can view results at anytime (do not ask for or respond with private data)
		- Once a survey expires, the data cannot be recovered
	- Create new survey form
- Create survey form
	- The form used to create a new survey by a user
	- Fields
		- Name (255 chars max)
		- Expiration time (radio select)
			- 1 day
			- 1 week
			- 1 month
		- Questions (1 or more)
			- Radio
			- Checkbox
			- Select
			- Text short form (255)
			- Text long form (1000)
		- Each question has a label, optional description, type and required checkbox
	- ID: Random, unique generated 
- View survey (/surveys?id=XXXXX)
	- Header
		- Link to view results
		- Countdown until deletion
		- Disclaimer that results are visible to anyone, don't submit private data
	- Body
		- Shows survey form to fill out
- View survey results (/surveys/results?id=XXXXX)
	- Header
		- Link to take survey
		- Link to download as JSON
		- Countdown until deletion
	- Body
		- Each question listed out
		- For radio, select and checkbox questions, show counts per picked choice
		- For text responses, show a grid of cards with answers
- JSON export (/surveys/json?id=XXXXXX)
	- Raw json output of survey
	```json
		{
			"title": "{survey title}",
			"created_at": "{created_date}",
			"expires at": "{expires_date}",
			"questions": [
				{
					"label": "{label}",
					"description": "{description}",
					"is_required": true,
					"type": "{type}",
					"responses": [
						{
							"date": "{response_date}",
							"value": "{response_value}"
						}
					]
				}
			]
		}
	```

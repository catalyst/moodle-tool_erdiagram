![GitHub Workflow Status (branch)](https://img.shields.io/github/actions/workflow/status/catalyst/moodle-tool_erdiagram/ci.yml?branch=master&label=ci)

### Moodle ER Diagram Generator

A proof of concept admin tool plugin created as part of the "Catathon" day
at Catalyst EU (https://www.catalyst-eu.net/). 2nd November 2023

Generate Mermaid ER (Entity Relationship) diagram markdown from plugin install.xml files

The form allows entry of the path to a plugin e.g. mod/label.
The db/install.xml file will be opened and the xml converted to mermaid markdown.
There is a checkbox to allow inclusion of Fields, type and comment for a more detailed/cluttered view.

A preview is displayed and the markup can be copied for further viewing and manipulation at

https://mermaid.live/

See also

https://mermaid.js.org/

https://github.com/mermaid-js/mermaid

https://docs.github.com/en/get-started/writing-on-github/working-with-advanced-formatting/creating-diagrams

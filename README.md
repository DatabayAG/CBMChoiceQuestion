# Question Plugin - CBMChoiceQuestion

## Requirements

| Component               | Min                                              | Max                                                                       | Link                      |
|-------------------------|--------------------------------------------------|---------------------------------------------------------------------------|---------------------------|
| PHP                     | ![](https://img.shields.io/badge/7.3-blue.svg)   | ![](https://img.shields.io/badge/7.4-blue.svg)                            | [PHP](https://php.net)    |
| ILIAS                   | ![](https://img.shields.io/badge/7.x-orange.svg) | ![](https://img.shields.io/badge/7.x-orange.svg)                          | [ILIAS](https://ilias.de) |
| CBMChoiceQuestionExport | ![](https://img.shields.io/badge/r7-blue.svg)    | [GitLab](https://gitlab.databay.de/Ilias-Plugins/CBMChoiceQuestionExport) |

---
## Table of contents

<!-- TOC -->
* [Question Plugin - CBMChoiceQuestion](#question-plugin---cbmchoicequestion)
  * [Requirements](#requirements)
  * [Table of contents](#table-of-contents)
  * [Concept document](#concept-document)
  * [Information](#information)
  * [Installation](#installation)
  * [Usage](#usage)
<!-- TOC -->

---

## Concept document

[Concept document](docs/Konzept%20CBM-Fragetyp-Plugin.pdf)

## Information

The **CBMChoiceQuestionExport** plugin can optionally be used to export CBM Question Solutions into an Excel table format.

## Installation

1. Clone this repository to **Customizing/global/plugins/Modules/TestQuestionPool/Questions/CBMChoiceQuestion**
2. Install the Composer dependencies
   ```bash
   cd Customizing/global/plugins/Modules/TestQuestionPool/Questions/CBMChoiceQuestion
   composer install --no-dev
   ```
   Developers **MUST** omit the `--no-dev` argument.


3. Login to ILIAS with an administrator account (e.g. root)
4. Select **Plugins** in **Extending ILIAS** inside the **Administration** main menu.
5. Search for the **CBMChoiceQuestion** plugin in the list of plugin and choose **Install** from the **Actions** drop-down.
6. Choose **Activate** from the **Actions** dropdown.

## Usage

ToDo
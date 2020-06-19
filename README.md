# [WP OAIPMH]

WordPress connector via OAI-PMH (lom format). OAI-PMH via picturae/OaiPmh package

## Features

* OAI-PMH lom on siteurl/oai

## Requirements

Needs Advanced Custom Fields or Advanced Custom Fields Pro to be activated and having fields for your CPT. Needs permalinks to be enabled.
Make sure the following packages are installed and activated before activating this plugin.

```
    "picturae/oai-pmh": "0.5.18",
    "zendframework/zend-diactoros": "*",
    "zendframework/zend-httphandlerrunner": "*",
    "this-repo/wpoaipmh": "*",

```

See public-display.php if your PHP version is not sufficient.

## Installation

You must install this plugin via the command-line. Make sure you have a completed composer install and applied all requirements before activating this plugin.

### via Command-line

After updating the composer file

```
composer install
```

Then activate the plugin.

## Known bugs

* picturae/OaiPmh package will NOT sent error 200 OK, for any thrown exceptions in PHP. It will sent error 400

## Assumptions

* Only lom format will be used
* Only publications (CPT = post) will be present. However the code is ready to be extended and filtered. See chapter `General`, entry `Extending sets` and `WordPress filters` of this document.

## Technical architecture / How it works

### Mass import existing items
@see instructions on admin/class-import-wp-bridge.php
Take care that this is set to CPT=post, you may override this (see file)

### ListSets
oai?verb=ListSets

### ListMetaDataFormats
oai?verb=ListMetadataFormats

### ListRecords
oai?verb=ListRecords&metadataPrefix=lom&set=publication

### ListRecords with resumptionToken
oai?verb=ListRecords&resumptionToken=[resumptionToken]

### GetRecord
oai?verb=GetRecord&identifier=leraar24:publication:18&metadataPrefix=lom



### General

* Never use any mysql(i) functions directly (kitteh's will die). Rely on $wpdb <https://codex.wordpress.org/Class_Reference/wpdb>
* We store all information in our own oai tables (see `database tables` in this document). This ensures persistence of data, even when posts are fully deleted (from trash).
* The oai id in tables is the post ID that WordPress is using
* On each update/save of a post the tables are (re-)populated for that specific post.
* Only CPT that are/were ever publicly published are visible in the oai list. See chapter `Published / deleted in OAI` in this document.
* Extending has been made possible using filters and PHP extends @see example.php
* Extending `sets` to more then just post CPT adjust Repository::listSets() and wpoaipmh_WP_bridge::$post_types
* Extending `taxonomies` adjust wpoaipmh_WP_bridge::$core_taxonomies, wpoaipmh_WP_bridge::__construct() and the output function wpoaipmh_OAI_WP_bridge::get_meta() _plus_ make sure you insert a new taxonomy id in the `l24_oai_taxonomy` table
* Extending `edustandaard sectorids` adjust wpoaipmh_OAI_WP_bridge::$edustandaard_sectorids

### Database installation

On activation of the plugin. It will check for existence of the tables before creating them.

### Database tables

```
l24_oai
Base table, stores all information of CPT = post, except taxonomies

l24_oai_taxonomy
Base table of all taxonomies (name of taxonomy), pre-populated with sector, post_competence, post_tag

l24_oai_terms
Table holding all the terms (term id, tax id, term). Note that actual terms can be duplicated due to presence in multiple taxonomies

l24_oai_term_relationships
Table with term relationships (term id, oai id)
```

### From / till

We are using the column `modified_date`. NOT `published_date`. Therefore the wpoaipmh_OAI_WP_bridge::get_earliest_date() relies on MIN() of `modified_date`

### Files (the important ones)

```
public/class-public.php
includes/class-oaipmh.php
includes/class-wp-bridge.php
includes/class-oai-wp-bridge.php
admin/class-import-wp-bridge.php
```

#### public/class-public.php

Rewrite rule `oai` to partials/public-display.php

#### includes/class-oaipmh.php

Higher level functions as implements of Picturae\OaiPmh

#### includes/class-wp-bridge.php

Base class to insert and update CPT data and taxonomies upon save (normal save + AJAX inline save).
@see Line with WP_OAIPMH_FORCE_INLINE_SAVE to externally update/save in public function update_table_core_post, also @see class wpoaipmh_import_bridge for an example

#### includes/class-oai-wp-bridge.php

Extends wpoaipmh_WP_bridge; Used in class-oaipmh.php; Queries WordPress database. Creates XML output structure using functions helper_meta_create_structure and helper_meta_add_sub. Returns string using function helper_meta_to_string.

#### admin/class-import-wp-bridge.php

Only included when is_admin() is true; Extends wpoaipmh_WP_bridge; Used to populate the oai tables. @see function import_into_oai how to run

### Published / deleted in OAI

* _Every_ oai database public SELECT query has this constraint: `is_ever_publicly_published = 1`. This prevents posts to be published in oai when they were never publicly visible.
* Flag `is_ever_publicly_published` is set to true (1) when at one point in time a post has `post_status = publish` and `post_password` is empty
* Flag `is_publicly_published` is set to true (1) when a post has `post_status = publish` and `post_password` is empty. Else it will be set to false (0)
* Flag `is_deleted` is set to true (1) and `deleted_date` is set to NOW when a post has `post_status = trash`. Else it will be set to false (0)

### Taxonomy replacements

Taxonomy `sector` term `MBO` will be replaced with vdex term `BVE`


### WordPress filters

* 'wpoaipmh/oai_record_do_tax/'.$tax
* 'wpoaipmh/oai_record_meta'
* 'wpoaipmh/oai_repositoryName'
* 'wpoaipmh/oai_listsets'
* 'wpoaipmh/core_taxonomies'
* 'wpoaipmh/post_types'
* 'wpoaipmh/acf_do_sectors'
* 'wpoaipmh/acf_do_publication_revision_date'
* 'wpoaipmh/acf_do_publication_partner'
* 'wpoaipmh/post_excerpt'
* 'wpoaipmh/published_date_column_name'
* 'wpoaipmh/modified_date_column_name'



### Pull requests

While developing only pull request to the `test` branch are accepted based on feature branches. Solving a bugfix, feature or hotfix use the branching structure as stated below.
Commits in the pull request need to follow the commit guidelines.

### Branches

All branch names should be written in correct English. The use of abbreviations is not allowed as this can be confusing to developers who are not well known with a project.

#### 1. master

We consider `origin/master` to be the main branch where the source code of HEAD always reflects a production-ready state.

#### 2. acceptance

The `origin/acceptance` branch will contain the latest major release from the `test` branch. The acceptance branch is used for intensive testing. After acceptance of the testing team the branch is merged in the `master` branch. (This branch is mainly used on repositories which are in production, during the initial development this branch is skipped.)

#### 3. test

We consider `origin/test` to be the main branch where the source code of HEAD always reflects a state with the latest delivered development changes for the next release.
Must merge back into `acceptance` for production servers or `master` for development servers.

#### 4. hotfix-*

The `hotfix-*` branches are very much like feature branches in that they are also meant to prepare for a new production feature, albeit unplanned.
Must merge back into `test`, `acceptance` and `master`

#### 5. bugfix-*

The `bugfix-*` branches are used to solve non urgent bugs for an upcoming or distant future release.
[!!!] For urgent bugs use the `hotfix` branches.
Must merge back into `test`

#### 6. feature-*

The `feature-*` branches are used to develop new features for the upcoming or a distant future release.
Must merge back into `test`

### Commit guidelines

#### 1. Language

All commit messages should be written in correct English. The use of abbreviations is not allowed as this can be confusing to developers who are not well known with a project.

#### 2. Structure of a commit message

The commit message determines the nature of your commit. Based on the message it
should be clear what the change is. Especially the subject of the message should
be short and clear. The subject is matched against a regular expression for
validation:

`^(\[!!!\])?\[(CONFIG|FEATURE|TASK|BUGFIX|CLEANUP|MERGE)\]\ [A-Z]`

This means that a commit message must start with a **tag** (sometimes more),
followed by a **space** and a short **description** which should start with an
uppercase letter. There are a couple predefined keywords which can be used as
tag, surrounded by brackets:

* `[CONFIG]`
* `[FEATURE]`
* `[BUGFIX]`
* `[CLEANUP]`
* `[TASK]`
* `[MERGE]`

Besides these there is a special tag which can be used in *addition* to the previous mentioned tags:

* `[!!!]`

Prefixing your change with this tag, will mark it as being breaking. In case you add a breaking change, add proper description and describe what may break and why. This way you will let other developers know what to expect.

All lines should be limited to 80 characters. [#]_ When adding a detailed description don't continue inside the summary, but start a new sentence.

#### 3. Tags

When choosing a tag, you can use the following guidelines.

`[CONFIG]` When a change is mainly a configuration change. For example change credentials of the database, or configure an extension.

`[FEATURE]` When implementing a new feature, like a new extension, or new functionality. This can also apply to new CSS or new styling.

`[BUGFIX]` When you changed code to fix a bug. This can also be a change in CSS.

`[CLEANUP]` When cleaning up the project, like removing temporary files, removing obsolete code, modifying the .gitignore, etc.

`[TASK]` Other issues which do not fit in the previous mentioned tags. Updating an extension is an example of a task.

`[MERGE]` A merge commit can only contain a merge of a feature to the development branch or the merge of the development branch to the master branch when going to production. No other changes should be part of a merge.

`[!!!]` Breaking change. Use this when after this change manual changes should be applied like database changes or when an API is modified which might influence other parts. This tag should be placed in front of other tags, never by itself.

#### 4. Examples

`[BUGFIX]` Fixed date format bug
*A bug where the date was displayed in an incorrect format.*
*Resolves: #12334*

`[FEATURE]` Change service level API for feature X
*Feature X has been implemented for the service level API.*

`[TASK]` Updated extension X
*Updated extension X from version 1.4 to 1.5.1*

`[CLEANUP]` Removed unused extension Y
*Removed extension Y, is it is obsolete.*

`[MERGE]` Merged X onto Y

`[!!!][FEATURE]` Change service level API for feature X
*Feature X has been implemented for the service level API.*
*Can breakdown API dependencies*

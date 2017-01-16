# pubmed-cache

PubMed is one the primary systems used by physicians to track citations for biomedical literature. The PubMed system has not seen an overhaul in quite some time, and although it claims to offer systems for programmatically providing information into other systems, at least one of those methods (the "Create RSS" funciton for a search) has huge bugs that prevent using it properly.<sup>1</sup>

This is a PHP/MySQL tool built to cache search results and return them in a human-readable RSS feed that links to the original PubMed citation.

## Installation

To install, clone this repository to a subdirectory of a WordPress installation off the main installation root directory. You'll need to edit `inc/env.php` to set it up for your environment. The included `db_schema.sql` provides the schema you'll need for the table we're using (I would advise calling it "pubmed" lest you want to rewrite the sql queries).

By default, this uses WordPress authentication, allowing administrators only to access the page.

## Usage

Use the "Add New" button to add new feeds. Every feed needs a name and a PubMed Search Results URL.

The link button provides the link for the RSS feed.

The recycle button refreshes the feed instantly.

The pencil button allows you to edit the name or search results URL. Editing a feed means it will be refreshed.

<sup>1</sup> Using this function produces a valid, useful RSS feed until there's a new result in your search. At that point, only the update shows up in the feed. There are several sources on the web that advise doing various things to fix this, but in testing none of them worked.

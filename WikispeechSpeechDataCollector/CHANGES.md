# Wikispeech Speech Data Collector version changelog

## About

This project use [Semantic versioning](https://semver.org/). Major.Minor.Patch.

## Versioning

Add new entries to the top of current -SNAPSHOT section,
i.e. in reversed chronological order.

Annotate your code with @since using the current -SNAPSHOT version.
E.g. when the current is 0.1.2-SNAPSHOT, use @since 0.1.2 in the code.

## On release

Remove -SNAPSHOT, set date and create a new -SNAPSHOT section.

If version bump is greater than originally expected,
e.g. from 0.1.2-SNAPSHOT to 0.2.0,
then replace all @since 0.1.2 tags in the code to 0.2.0 using a new task.

Update [mediawiki.org documentation](https://www.mediawiki.org/wiki/Extension:WikispeechSpeechDataCollector)
the match the new release version.

Update the version in extension.json.

## Versions

### 0.1.0-SNAPSHOT

202Y-MM-DD

* [T267783](https://phabricator.wikimedia.org/T267783) Add recording functionality to the frontend
* [T267116](https://phabricator.wikimedia.org/T267116) Introduce ApiCRUD, a generic API for accessing persistent objects.
* [T272023](https://phabricator.wikimedia.org/T272023) Introduce Multi Content Revision CRUD and RecordingAnnotations.
* [T273608](https://phabricator.wikimedia.org/T273608) Add speech recording file wikipage identity to Recording.
* [T273448](https://phabricator.wikimedia.org/T273448) Introduce CRUDContext to support multiple persistency layers.
* [T269335](https://phabricator.wikimedia.org/T269335) Create special page for recording
* [T266004](https://phabricator.wikimedia.org/T266004) Remove use of &$byref from PersistentVisitor.
* [T267117](https://phabricator.wikimedia.org/T267117) Introduce Persistent serialization for API.
* [T266004](https://phabricator.wikimedia.org/T266004) Remove use of &$byref from CRUD.
* [T267111](https://phabricator.wikimedia.org/T267111) Extract reusable test data providers from CRUD-tests.
* [T267109](https://phabricator.wikimedia.org/T267109) Correctly deserialize null values in CRUDs.
* [T267112](https://phabricator.wikimedia.org/T257109) Introduce UUID helper class.
* [T267114](https://phabricator.wikimedia.org/T267114) Extract reusable Persistent IsSame constraints.
* [T257109](https://phabricator.wikimedia.org/T257109) Create initial boilerplate extension.

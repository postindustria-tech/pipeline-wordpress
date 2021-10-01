# API Specific CI/CD Approach
This API is a different from the common-ci approach.

Build and Test stage takes `buildType` parameter that can have two values either `Development` or `Production`.

`Development:` Build and test stage will use submodule references for dependencies where the dependency is relative to the local file system, so git references need to be updated to refer to the updated dependencies.
`Production:` Build and test stage will use the publically released packages so in this case composer.json will need to be updated
to get the updated packages.

By default build-and-test.yml uses the submodules reference and create-packages.yml uses the public packages.

# WordPress Build Plugin Approach

Wordpress Pipeline Plugin artifacts creation requires dependent libraries to be packaged with the artifacts so create-packages.yml involves a job that uses actual external package. his is not required for internal pipelines such as `build-and-test.yml` as it is acceptable to just use submodule references. However, before creating artifacts from master branch that will be deployed externally, the solution is required to work with external packages. `BuildWithAutomatedRelease' variable in `pipeline-wordpress-create-packages` pipeline will be used as a solution. The only dependency here is on `pipeline-php-cloudrequestengine` package. So there are two scenarios.

1. `BuildWithAutomatedRelease` value will be `On` When we don't have any changes to release for dependency package along with wordpress changes. Package artifacts will be created as part of the automation process.
2. `BuildWithAutomatedRelease` variable will have value as `Off`, when there are dependent changes in package `pipeline-php-cloudrequestengine` waiting to be released first. Once dependent package is released we have to manually run the `pipeline-wordpress-create-packages` pipeline again in order to complete the release.

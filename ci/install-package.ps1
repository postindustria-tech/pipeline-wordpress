param (
    [Parameter(Mandatory=$true)]
    [string]$RepoName,
    [Parameter(Mandatory=$true)]
    [string]$Version
)

./php/build-project.ps1 -RepoName $RepoName/lib

exit $LASTEXITCODE

# Contributor Guide

Do not hesitate to contribute to DSQL. We have made it safe and simple for you to contribute and if your contribution is not ideal, the rest of the team will help you to finalize it. You must follow the guidelines below.

## Cloning and Installing Locally

 - Use `git clone` before doing any changes instead of ZIP distribution.
 - install `git flow`, I recommend getting a refined version from here: http://github.com/petervanderdoes/gitflow
 - execute `git flow init`
 - Read http://danielkummer.github.io/git-flow-cheatsheet/ explaining basic workflow. Focus on "feature" section.

## Planning your change

 - if you are renaming internal methods, note your changes into CHANGES.md file
 - if you performing major changes (which may affect developers who use DSQL), discuss in Slack #dsql first.

## Creating your own feature or fix

 - decide what feature you're working. Use prefix "add-" or "fix-". Use dashes instead of spaces.
 - make sure your branch is consistent with other branches. See [https://github.com/atk4/dsql/branches/all](https://github.com/atk4/dsql/branches/all)
 - execute `git feature start fix-psr-compatibility`. If you already modified code, `git stash` it.
 - use `git stash pop` to get your un-commited changes back
 - install and execute `phpunit` to make sure your code does not break any tests
 - update or add relevant test-cases. Remember that Unit tests are designed to perform low-level tests preferably against each method.
 - update or add documentation section, if you have changed any behavior. RST is like Markdown, but more powerful. [http://docutils.sourceforge.net/docs/user/rst/quickref.html](http://docutils.sourceforge.net/docs/user/rst/quickref.html)
 - see [docs/README.md](docs/README.md) on how to install "sphinx-doc" locally and how to make documentation.
 - open docs/html/index.html in your browser and review documentation changes you have made
 - commit your code. Name your commits consistently. See [https://github.com/atk4/dsql/commits/develop](https://github.com/atk4/dsql/commits/develop)
 - use multiple comments if necessary. I recommend you to use "Github Desktop", where you can even perform partial file commits.
 - once commits are done run `git feature publish fix-psr-compatibility`. 
 
## Create Pull Request

 - Go to [http://github.com/atk4/dsql](http://github.com/atk4/dsql) and create Pull Request
 - In the description of your pull request, use screenshots of new functionality or examples of new code.
 - Go to #dsql on our Slack and ask others to review your PR.
 - Allow others to review. Never Merge your own pull requests. 

## If you do not have access to atk4/dsql

 - Fork atk4/dsql repository.
 - Follow same instructions as above, but use your own repository name
 - If you contribute a lot, it would make sense to [set up codeclimate.com for your repo](https://codeclimate.com/github/signup). 
 - You can also enable Travis-CI for your repository easily.

## Verifying your code

 - Once you publish your branch, Travis will start testing it: [https://travis-ci.org/atk4/dsql/branches](https://travis-ci.org/atk4/dsql/branches)
 - When your PR is ready, Travis will run another test, to see if merging your code would cause any failures: [https://travis-ci.org/atk4/dsql/pull_requests](https://travis-ci.org/atk4/dsql/pull_requests)
 - It's important that both tests are successful
 - Once your branch is public, you should be able to run Analyze on CodeClimate: [https://codeclimate.com/github/atk4/dsql/branches](https://codeclimate.com/github/atk4/dsql/branches) specifically on your branch.


 
 
 
 
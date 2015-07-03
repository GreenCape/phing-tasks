# Phing Tasks

![SensioLabsInsight](https://insight.sensiolabs.com/projects/d3a1f999-20d8-4496-8828-73ec563a0846/mini.png)
[![Code Climate](https://codeclimate.com/github/GreenCape/phing-tasks/badges/gpa.svg)](https://codeclimate.com/github/GreenCape/phing-tasks)
[![Test Coverage](https://codeclimate.com/github/GreenCape/phing-tasks/badges/coverage.svg)](https://codeclimate.com/github/GreenCape/phing-tasks/coverage)
[![Latest Stable Version](https://poser.pugx.org/greencape/phing-tasks/v/stable.png)](https://packagist.org/packages/greencape/phing-tasks)
[![Build Status](https://api.travis-ci.org/GreenCape/phing-tasks.svg?branch=master)](https://travis-ci.org/greencape/phing-tasks)

A collection of Phing tasks, mainly for use in GreenCape/build

## Installation

Use Composer to install the Phing tasks

```
$ composer require [--dev] "greencape/phing-tasks":"*"
```

To import the tasks into your installation, add

```
<taskdef file="${project.basedir}/vendor/greencape/phing-tasks/tasks.properties"/>
```

to your `build.xml`.

## Usage

Type
```
$ .vendor/bin/phing -l
```

for the list of available targets, or 

```
$ .vendor/bin/phing help
```

to get some additional info.

## Documentation

The documentation is located in the `docs` directory. You can [view the documentation online](http://htmlpreview.github.io/?https://github.com/GreenCape/phing-tasks/master/docs/index.html). 

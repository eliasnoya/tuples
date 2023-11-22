<?php

namespace Tuples\Container;

enum DependencyType: string
{
    case SINGLETON = 'SINGLETON';
    case INSTANCEABLE = 'INSTANCEABLE';
}

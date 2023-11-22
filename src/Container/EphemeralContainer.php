<?php

namespace Tuples\Container;

use Tuples\Container\Contracts\AbstractContainer;

/**
 * An ephemeral container implementation designed to exist within a specific context, such as a request lifecycle.
 * This container lives only until its parent process, for example, a request, terminates.
 * It is particularly useful in a RoadRunner worker implementation, where the Container serves the entire application,
 * and the EphemeralContainer is associated with $request->container, allowing the attachment of users, tenants,
 * or any data shared throughout the request lifecycle.
 */
class EphemeralContainer extends AbstractContainer
{
}

<?php

/*
** Copyright (C) 2010-2012 Kerouanton Albin, Smedts Jérôme
**
** This program is free software; you can redistribute it and/or modify
** it under the terms of the GNU General Public License as published by
** the Free Software Foundation; either version 2 of the License, or
** (at your option) any later version.
**
** This program is distributed in the hope that it will be useful,
** but WITHOUT ANY WARRANTY; without even the implied warranty of
** MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
** GNU General Public License for more details.
**
** You should have received a copy of the GNU General Public License along
** with this program; if not, write to the Free Software Foundation, Inc.,
** 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
*/

namespace DP\GameServer\SteamServerBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use DP\GameServer\SteamServerBundle\Entity\SteamServer;

/**
 * @author Albin Kerouanton 
 */
class QueryInjector
{
    private $serviceContainer = null;
    
    /**
     * Inject the SteamQuery service into entity
     * 
     * @param \Doctrine\ORM\Event\LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        
        if ($entity instanceof SteamServer) {
            $query = $this->getQueryService()->getServerQuery(
                $entity->getMachine()->getPublicIp(), $entity->getPort());
            
            $entity->setQuery($query);
        }
    }
    
    /**
     * Set service container
     * 
     * @param ServiceContainer $sc 
     */
    public function setServiceContainer($sc)
    {
        $this->serviceContainer = $sc;
    }
    
    /**
     * Get steam query service
     * 
     * @return \DP\GameServer\SteamServerBundle\Service\Query
     * @throws Exception 
     */
    private function getQueryService()
    {
        if (is_null($this->serviceContainer)) {
            throw new Exception('The service container is not yet set.');
        }
        
        return $this->serviceContainer->get('query.steam');
    }
}

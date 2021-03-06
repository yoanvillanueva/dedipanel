<?php

/*
** Copyright (C) 2010-2013 Kerouanton Albin, Smedts Jérôme
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

namespace DP\GameServer\MinecraftServerBundle\Listener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use DP\GameServer\MinecraftServerBundle\Entity\MinecraftServer;
use DP\Core\MachineBundle\Entity\Machine;

/**
 * @author Albin Kerouanton 
 */
class ConfigModifier
{
    protected $container;
    
    /**
     * Set service container
     * 
     * @param ServiceContainer $container
     */
    public function setServiceContainer($container)
    {
        $this->container = $container;
    }
    
    /**
     * Get steam query service
     * 
     * @return \DP\GameServer\SteamServerBundle\Service\Query
     * @throws Exception 
     */
    protected function getTwig()
    {
        if (is_null($this->container)) {
            throw new Exception('The service container is not yet set.');
        }
        
        return $this->container->get('twig');
    }
    
    /**
     * Maj des scripts du serveur si la config du serveur (port, maxplayers, dir) a été modifié
     * Ou si la config du jeu (bin, binDir, installName, launchName, map, orangebox, source) a été modifié
     * Ou si l'IP publique de la machine a été modifié
     * 
     * Réinstallation du serveur
     * Si des modifs ont été faites sur la machine (IP publique, home, user)
     * Ou si le serveur de jeu n'est plus sur la meme machine
     * Ou si le jeu du serveur est modifié
     * 
     * @param \Doctrine\ORM\Event\PreUpdateEventArgs $args
     */
    public function preUpdate(PreUpdateEventArgs $args)
    {
        $entity = $args->getEntity();
        
        if ($entity instanceof MinecraftServer) {
            if ($args->hasChangedField('port') || $args->hasChangedField('maxplayers') 
                || $args->hasChangedField('name') || $args->hasChangedField('queryPort') 
                || $args->hasChangedField('rconPort') || $args->hasChangedField('rconPassword')) {
                try {
                    $entity->modifyServerPropertiesFile();
                }
                catch (\Exception $e) {}
            }
            if ($args->hasChangedField('minHeap') || $args->hasChangedField('maxHeap')
                || $args->hasChangedField('dir')) {
                try {
                    $entity->uploadShellScripts($this->getTwig());
                }
                catch (\Exception $e) {}
            }
        }
        elseif ($entity instanceof Machine) {
            // Upload des scripts si l'IP public ou le home de la machine a été modifié
            if ($args->hasChangedField('publicIp') || $args->hasChangedField('home')) {
                $servers = $entity->getGameServers();
                
                foreach ($servers AS $server) {
                    if (!$server instanceof MinecraftServer) continue;
                    
                    if ($args->hasChangedField('publicIp')) {
                        try {
                            $server->modifyServerPropertiesFile();
                        }
                        catch (\Exception $e) {}
                    }
                    if ($args->hasChangedField('home')) {
                        try  {
                            $server->uploadShellScripts($this->getTwig());
                        }
                        catch (\Exception $e) {}
                    }
                }
            }
        }
    }
}

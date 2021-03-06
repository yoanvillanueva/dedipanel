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

namespace DP\GameServer\SteamServerBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use DP\GameServer\SteamServerBundle\Entity\SteamServer;

class AddSteamServerType extends AbstractType
{    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        	->add('machine', 'entity', array(
                'label' => 'game.selectMachine', 'class' => 'DPMachineBundle:Machine'))
            ->add('name', 'text', array('label' => 'game.name'))
            ->add('port', 'integer', array('label' => 'game.port'))
            ->add('game', 'entity', array(
                'label' => 'game.selectGame', 
                'class' => 'DPGameBundle:Game',
                'query_builder' => function($repo) {
                    return $repo->getQBAvailableSteamGames();
                }
            ))
            ->add('dir', 'text', array('label' => 'game.dir'))
            ->add('maxplayers', 'integer', array('label' => 'game.maxplayers'));
            
        /*$builder->add('mode', 'choice', array(
            'choices' => SteamServer::getModeList(), 
            'empty_value' => 'steam.chooseGameMode',
            'label' => 'steam.gameMode', 
        ));*/
                
        $builder
            ->add('rconPassword', 'text', array('label' => 'game.rcon.password'))
            ->add('svPassword', 'text', array('label' => 'steam.svPassword', 'required' => false))
            ->add('alreadyInstalled', 'choice', array(
                'choices'   => array(1 => 'game.yes', 0 => 'game.no'), 
                'label'     => 'game.isAlreadyInstalled', 
                'mapped'    => false, 
                'expanded'  => true
            ));
    }

    public function getName()
    {
        return 'dp_gameserver_steamserverbundle_addsteamservertype';
    }
}

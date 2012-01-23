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

namespace DP\GameServer\SteamServerBundle\SteamQuery;

use DP\GameServer\GameServerBundle\Socket\Socket;
use DP\GameServer\SteamServerBundle\Service\SteamPacketFactory;
use DP\GameServer\GameServerBundle\Socket\Packet;
use DP\GameServer\GameServerBundle\Socket\PacketCollection;

use DP\GameServer\GameServerBundle\Socket\Exception\ConnectionFailedException;
use DP\GameServer\GameServerBundle\Socket\Exception\NotConnectedException;
use DP\GameServer\GameServerBundle\Socket\Exception\RecvTimeoutException;
use DP\GameServer\SteamServerBundle\SteamQuery\Exception\ServerTimeoutException;

/**
 * @author Albin Kerouanton 
 */
class SteamQuery
{
    private $container;
    private $socket;
    private $packetFactory;

    protected $challenge;
    protected $latency;
    protected $serverInfos;
    protected $players;
    protected $rules;
    
    /**
     * Constructor
     * 
     * @param Service Container $container
     * @param string $host
     * @param int $port
     */
    public function __construct($container, $host, $port)
    {
        // On ne déclare pas les 2 callbacks simultanément
        // Puisque le 2nd fait appel au 1er
        $callbacks = array('isMultiResp' => function ($packet) {
                if (is_null($packet)) return false;
                
                return $packet->getLong() == -2;
            }); 
        $callbacks['recvMultiResp'] = 
            function(Packet $packet, Socket $socket) use($container, $callbacks) {
                $splittedPackets = new PacketCollection();
                $respId = null;

                do {                
                    // On récupère l'id de la transmission
                    // Et on vérifie que les packets récupérés aient le même ID
                    $id = $packet->getLong();
                    if (!$respId) {
                        $respId = $id;
                    }
                    elseif ($respId != $id) {
                        $packet = null;
                        $packet = $socket->recv(false);
                        continue;
                    }

                    $infosPacket = $packet->getByte();
                    $nbrePacket = $infosPacket & 0xF;
                    $packetId = $infosPacket >> 4;

                    $splittedPackets[$packetId] = $packet;

                    // On remet à zéro le packet pour ne pas avoir de boucle infinie
                    $packet = null;
                    if (count($splittedPackets) < $nbrePacket) {
                        $packet = $socket->recv(false);
                    }
                    $isMultiResp = call_user_func($callbacks['isMultiResp'], $packet);
                } while (!empty($packet) && $isMultiResp == true);
                
                // Les réponses multi packet une fois réassemblé
                // Comence par l'entier -1
                $ret = $splittedPackets->reassemble();
                $ret->getLong();
                
                return $ret;
            };
        
        $this->container = $container;
        $this->packetFactory = $container->get('packet.factory.steam');
        
        $this->socket = $container->get('socket')->getUDPSocket($host, $port, $callbacks);
        
        try {
            $this->socket->connect();
        }
        catch (ConnectionFailedException $e) {}
    }
    
    /**
     * Get the server info
     * 
     * @return array
     * @throws Exception\ServerTimeoutException 
     */
    public function getServerInfos()
    {
        if (!isset($this->serverInfos)) {
            try {
                $this->socket->send($this->packetFactory->A2S_INFO());
                $resp = $this->socket->recv();

                $infos = $resp->extract(array('header' => 'byte',
                    'protocol' => 'byte',
                    'serverName' => 'string',
                    'map' => 'string',
                    'gameDir' => 'string',
                    'gameName' => 'string',
                    'appId' => 'short',
                    'players' => 'byte',
                    'maxPlayers' => 'byte',
                    'bot' => 'byte',
                    'serverType' => 'byte',
                    'os' => 'byte',
                    'password' => 'byte',
                    'vac' => 'byte',
                    'gameVer' => 'string',
                    'edf' => 'byte'));

                if ($infos['edf'] & 0x080) {
                    $infos['port'] = $resp->getShort();
                }
                elseif ($infos['edf'] & 0x040) {
                    $infos['hltv_port'] = $resp->getShort();
                    $infos['hltv_name'] = $resp->getString();
                }
                elseif ($infos['edf'] & 0x020) {
                    $infos['keywords'] = $resp->getString();
                }

                $this->serverInfos = $infos;
            }
            catch (RecvTimeoutException $e) {
                throw new Exception\ServerTimeoutException();
            }
            catch (NotConnectedException $e) {
                $this->serverInfos = array();
            }
        }
        
        return $this->serverInfos;
    }
    
    /**
     * Get server challenge
     * 
     * @return long
     */
    protected function getChallenge()
    {
        if (!isset($this->challenge)) {
            try {
                $this->socket->send($this->packetFactory->A2S_PLAYER("\xFF\xFF\xFF\xFF"));
                $resp = $this->socket->recv();

                $data = $resp->extract(
                    array('header' => 'byte', 'challenge' => 'long'));

                if ($data['header'] == 65) {
                    $this->challenge = $data['challenge'];
                }
            }
            catch (RecvTimeoutException $e) {
                throw new ServerTimeoutException();
            }
        }
        
        return $this->challenge;
    }
    
    /**
     * Get players list
     * 
     * @return array
     * @throws ServerTimeoutException 
     */
    public function getPlayers()
    {
        if (!isset($this->players)) {
            try {
                $this->socket->send($this->packetFactory->A2S_PLAYER($this->getChallenge()));
                $resp = $this->socket->recv();

                $players = array();
                $header = $resp->extract(
                    array('header' => 'byte', 'nb_players' => 'byte'));


                for ($i = 0, $max = $header['nb_players']; $i < $max; ++$i) {
                    $players[$i] = $resp->extract(array('id' => 'byte', 
                        'nom' => 'string', 'score' => 'long', 'timeConnected' => 'float'));
                }

                $this->players = $players;
            }
            catch (RecvTimeoutException $e) {
                throw new ServerTimeoutException();
            }
            catch (NotConnectedException $e) {
                $this->players = array();
            }
        }
        
        return $this->players;
    }
    
    /**
     * Get rules list
     * @return type
     * @throws ServerTimeoutException 
     */
    public function getRules()
    {
        if (!isset($this->rules)) {
            try {
                $this->socket->send($this->packetFactory->A2S_RULES($this->getChallenge()));
                $resp = $this->socket->recv();
                
                $rules = array();
                $header = $resp->extract(
                    array('header' => 'byte', 'nb_rules' => 'short'));

                if ($header['header'] == 69) {
                    for ($i = 0, $max = $header['nb_rules']; $i < $max; ++$i) {
                        $rules[$i] = $resp->extract(
                            array('name' => 'string', 'value' => 'string'));
                    }

                    $this->rules = $rules;
                }
            }
            catch (RecvTimeoutException $e) {
                throw new ServerTimeoutException();
            }
            catch (NotConnectedException $e) {
                $this->rules = array();
            }
        }
        
        return $this->rules;
    }
    
    /**
     * Get the server latency
     * @return float
     */
    public function getLatency()
    {
        if (!isset($this->latency)) {
            $packet = $this->packetFactory->A2S_INFO();
            
            try {
                $ping = microtime(true);
                $this->socket->send($packet);
                $resp = $this->socket->recv();
                $this->latency = round((microtime(true) - $ping) * 1000);
            }
            catch (RecvTimeoutException $e) {
                $this->latency = false;
            }
            catch (NotConnectedException $e) {
                $this->latency = false;
            }
        }
        
        return $this->latency;
    }
    
    /**
     * Check if the server is online
     * @return bool 
     */
    public function isOnline()
    {
        return ($this->getLatency() != false);
    }
    
    /**
     * Alias of isOnline method
     */
    public function getIsOnline()
    {
        return $this->isOnline();
    }
}
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

namespace DP\VoipServer\VoipServerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use DP\Core\MachineBundle\Entity\Machine;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * DP\VoipServer\VoipServer\Entity\VoipServer
 *
 * @ORM\Table(name="voipserver")
 * @ORM\Entity()
 * @ORM\InheritanceType("JOINED")
 * @ORM\DiscriminatorColumn(name="servertype", type="string")
 * @ORM\DiscriminatorMap({"mumble" = "DP\VoipServer\MumbleServerBundle\Entity\MumbleServer"})
 */
class VoipServer {
    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    
    /**
     * @var integer $portMumble
     *
     * @ORM\Column(name="portMumble", type="integer")
     * @Assert\Min(limit=1, message="voipserver.assert.port")
     * @Assert\Max(limit=65536, message="voipserver.assert.port")
     */
    private $portMumble = 6502;

    /**
     * @var string $iceSecret
     *
     * @ORM\Column(name="iceSecret", type="string", length=32, nullable=true)
     */
    private $iceSecret;
    
        /**
     * @ORM\ManyToOne(targetEntity="DP\Core\MachineBundle\Entity\Machine", inversedBy="voipserver")
     * @ORM\JoinColumn(name="machineId", referencedColumnName="id")
     * @Assert\NotNull(message="voipserver.assert.machine")
     */
    protected $machine;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set portMumble
     *
     * @param integer $portMumble
     * @return VoipServer
     */
    public function setPortMumble($portMumble)
    {
        $this->portMumble = $portMumble;
    
        return $this;
    }

    /**
     * Get portMumble
     *
     * @return integer 
     */
    public function getPortMumble()
    {
        return $this->portMumble;
    }
    
    /**
     * Set iceSecret
     *
     * @param integer $iceSecret
     * @return VoipServer
     */
    public function seticeSecret($iceSecret)
    {
        $this->iceSecret = $iceSecret;
    
        return $this;
    }

    /**
     * Get iceSecret
     *
     * @return integer 
     */
    public function geticeSecret()
    {
        return $this->iceSecret;
    }
     /**
     * Set machine
     *
     * @param Machine $machine
     */
    public function setMachine(Machine $machine)
    {
        $this->machine = $machine;
    }

    /**
     * Get machine
     *
     * @return Machine
     */
    public function getMachine()
    {
        return $this->machine;
    }
}
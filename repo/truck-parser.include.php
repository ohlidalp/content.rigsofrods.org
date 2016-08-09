<?php

/**
	Rigs of Rods repository plugin
	Written by Petr "only_a_ptr" Ohlidal, 2016
	
	Licensed as GPLv3 
*/

/// Processes the "truck" format
/// 
/// Specification (select working location):
///     https://github.com/only-a-ptr/rigs-of-rods/blob/truckparser-revisited/source/main/resources/rig_def_fileformat/ReadMe.txt
///     https://github.com/RigsOfRods/rigs-of-rods/tree/master/source/main/resources/rig_def_fileformat/ReadMe.txt 
/// Documentation: http://docs.rigsofrods.org/technical/fileformat-truck
class TruckParser
{
    public function reset()
    {
        $this->line_number = 1;
        $this->line = null;
        $this->messages = array();
        
        $this->file = array(
            'authors' => array(),
            'description' => "",
            'fileformatversion' => null,
            'fileinfo' => null,
            'guid' => null
        );
    }
    
    public function process_line($line)
    {
        $line = trim($line);
        if (strlen($line) > 0 && $line[0] != ';' && $line != '/')
        {
            $this->cur_line = $line;
            $this->args = null;
            $this->parse_line();
        }
        $this->line_number++;
    }
    
    private function parse_line()
    {
        if ($this->cur_keyword == "description")
        {
            if ($this->check_keyword("end_description"))
            {
                $this->cur_keyword = null;
                return;
            }
            $this->file['description'] .= "\n{$this->cur_line}";
            return;
        }
    
        if (false) { ; } // Dummy
        
        
        elseif ($this->check_keyword("add_animation               ")) {  }
        elseif ($this->check_keyword("airbrakes                   ")) {  }
        elseif ($this->check_keyword("animators                   ")) {  }
        elseif ($this->check_keyword("AntiLockBrakes              ")) {  }
        elseif ($this->check_keyword("axles                       ")) {  }
        elseif ($this->check_keyword("author                      ")) { $this->parse_author();  }
        elseif ($this->check_keyword("backmesh                    ")) {  }
        elseif ($this->check_keyword("beams                       ")) {  }
        elseif ($this->check_keyword("brakes                      ")) {  }
        elseif ($this->check_keyword("cab                         ")) {  }
        elseif ($this->check_keyword("camerarail                  ")) {  }
        elseif ($this->check_keyword("cameras                     ")) {  }
        elseif ($this->check_keyword("cinecam                     ")) {  }
        elseif ($this->check_keyword("collisionboxes              ")) {  }
        elseif ($this->check_keyword("commands                    ")) {  }
        elseif ($this->check_keyword("commands2                   ")) {  }
        elseif ($this->check_keyword("contacters                  ")) {  }
        elseif ($this->check_keyword("cruisecontrol               ")) {  }
        elseif ($this->check_keyword("description                 ")) {  }
        elseif ($this->check_keyword("detacher_group              ")) {  }
        elseif ($this->check_keyword("disabledefaultsounds        ")) {  }
        elseif ($this->check_keyword("enable_advanced_deformation ")) {  }
        elseif ($this->check_keyword("end                         ")) {  }
        elseif ($this->check_keyword("end_section                 ")) {  }
        elseif ($this->check_keyword("engine                      ")) {  }
        elseif ($this->check_keyword("engoption                   ")) {  }
        elseif ($this->check_keyword("engturbo                    ")) {  }
        elseif ($this->check_keyword("envmap                      ")) {  }
        elseif ($this->check_keyword("exhausts                    ")) {  }
        elseif ($this->check_keyword("extcamera                   ")) {  }
        elseif ($this->check_keyword("fileformatversion           ")) { $this->parse_fileformatversion();  }
        elseif ($this->check_keyword("fileinfo                    ")) { $this->parse_fileinfo();  }
        elseif ($this->check_keyword("fixes                       ")) {  }
        elseif ($this->check_keyword("flares                      ")) {  }
        elseif ($this->check_keyword("flares2                     ")) {  }
        elseif ($this->check_keyword("flexbodies                  ")) {  }
        elseif ($this->check_keyword("flexbody_camera_mode        ")) {  }
        elseif ($this->check_keyword("flexbodywheels              ")) {  }
        elseif ($this->check_keyword("forwardcommands             ")) {  }
        elseif ($this->check_keyword("fusedrag                    ")) {  }
        elseif ($this->check_keyword("globals                     ")) {  }
        elseif ($this->check_keyword("guid                        ")) { $this->parse_guid();  }
        elseif ($this->check_keyword("guisettings                 ")) {  }
        elseif ($this->check_keyword("help                        ")) {  }
        elseif ($this->check_keyword("hideInChooser               ")) {  }
        elseif ($this->check_keyword("hookgroup                   ")) {  }
        elseif ($this->check_keyword("hooks                       ")) {  }
        elseif ($this->check_keyword("hydros                      ")) {  }
        elseif ($this->check_keyword("importcommands              ")) {  }
        elseif ($this->check_keyword("lockgroups                  ")) {  }
        elseif ($this->check_keyword("lockgroup_default_nolock    ")) {  }
        elseif ($this->check_keyword("managedmaterials            ")) {  }
        elseif ($this->check_keyword("materialflarebindings       ")) {  }
        elseif ($this->check_keyword("meshwheels                  ")) {  }
        elseif ($this->check_keyword("meshwheels2                 ")) {  }
        elseif ($this->check_keyword("minimass                    ")) {  }
        elseif ($this->check_keyword("nodecollision               ")) {  }
        elseif ($this->check_keyword("nodes                       ")) {  }
        elseif ($this->check_keyword("nodes2                      ")) {  }
        elseif ($this->check_keyword("particles                   ")) {  }
        elseif ($this->check_keyword("pistonprops                 ")) {  }
        elseif ($this->check_keyword("prop_camera_mode            ")) {  }
        elseif ($this->check_keyword("props                       ")) {  }
        elseif ($this->check_keyword("railgroups                  ")) {  }
        elseif ($this->check_keyword("rescuer                     ")) {  }
        elseif ($this->check_keyword("rigidifiers                 ")) {  }
        elseif ($this->check_keyword("rollon                      ")) {  }
        elseif ($this->check_keyword("ropables                    ")) {  }
        elseif ($this->check_keyword("ropes                       ")) {  }
        elseif ($this->check_keyword("rotators                    ")) {  }
        elseif ($this->check_keyword("rotators2                   ")) {  }
        elseif ($this->check_keyword("screwprops                  ")) {  }
        elseif ($this->check_keyword("section                     ")) {  }
        elseif ($this->check_keyword("sectionconfig               ")) {  }
        elseif ($this->check_keyword("set_beam_defaults           ")) {  }
        elseif ($this->check_keyword("set_beam_defaults_scale     ")) {  }
        elseif ($this->check_keyword("set_collision_range         ")) {  }
        elseif ($this->check_keyword("set_inertia_defaults        ")) {  }
        elseif ($this->check_keyword("set_managedmaterials_options")) {  }
        elseif ($this->check_keyword("set_node_defaults           ")) {  }
        elseif ($this->check_keyword("set_shadows                 ")) {  }
        elseif ($this->check_keyword("set_skeleton_settings       ")) {  }
        elseif ($this->check_keyword("shocks                      ")) {  }
        elseif ($this->check_keyword("shocks2                     ")) {  }
        elseif ($this->check_keyword("slidenode_connect_instantly ")) {  }
        elseif ($this->check_keyword("slidenodes                  ")) {  }
        elseif ($this->check_keyword("SlopeBrake                  ")) {  }
        elseif ($this->check_keyword("soundsources                ")) {  }
        elseif ($this->check_keyword("soundsources2               ")) {  }
        elseif ($this->check_keyword("speedlimiter                ")) {  }
        elseif ($this->check_keyword("submesh                     ")) {  }
        elseif ($this->check_keyword("submesh_groundmodel         ")) {  }
        elseif ($this->check_keyword("texcoords                   ")) {  }
        elseif ($this->check_keyword("ties                        ")) {  }
        elseif ($this->check_keyword("torquecurve                 ")) {  }
        elseif ($this->check_keyword("TractionControl             ")) {  }
        elseif ($this->check_keyword("triggers                    ")) {  }
        elseif ($this->check_keyword("turbojets                   ")) {  }
        elseif ($this->check_keyword("turboprops                  ")) {  }
        elseif ($this->check_keyword("turboprops2                 ")) {  }
        elseif ($this->check_keyword("videocamera                 ")) {  }
        elseif ($this->check_keyword("wheels                      ")) {  }
        elseif ($this->check_keyword("wheels2                     ")) {  }
        elseif ($this->check_keyword("wings                       ")) {  }
    }
    
    private function check_keyword($keyword)
    {
        $keyword = trim($keyword);
        $res = (bool) strcasecmp(substr($this->cur_line, 0, strlen($keyword)) ,$keyword) == 0;
        if ($res)
        {
             $this->cur_keyword = strtolower($keyword);
        }
        return $res; 
    }
    
    private function add_message($msg)
    {
        $this->messages[] = "Line:{$this->line_number}, keyword:{$this->cur_keyword} | {$msg}";
    }
    
    private function parse_args($min_args)
    {
        $a = array();
        $sep = " \t|:,";
        $tok = strtok($this->cur_line, $sep);
        if ($tok == null)
        {
            $this->add_message("Unable to parse");
            return null;
        }
        $a[] = $tok;
        while ($tok = strtok($sep))
        {
            $a[] = $tok;
        }
        if (count($a) < $min_args)
        {
            $this->add_message("Not enough args, {$min_args} needed, got " . count($a));
            return null;
        }
        return $a;
    }
    
    public function get_info()
    {
        return $this->file;
    }
    
    // ----------------------------- Sections ----------------------------------
    
    private function parse_author()
    {
        $args = $this->parse_args(1);
        $author = array();
        
        if (count($args) > 1) { $author['role']     = $args[1]; }
        if (count($args) > 2) { $author['forum_id'] = (int) $args[2]; }
        if (count($args) > 3) { $author['name']     = $args[3]; }
        if (count($args) > 4) { $author['email']    = $args[4]; }
        
        $this->file['authors'][] = $author;
    }
    
    private function parse_fileformatversion()
    {
        $args = $this->parse_args(2);
        if ($args == null) { return; }
        
        $this->file['fileformatversion'] = (int) $args[1];
    }
    
    private function parse_fileinfo()
    {
        $args = $this->parse_args(2);
        if ($args == null) { return; }
        
        $fileinfo = array('unique_id' => $args[1]);
        
        if (count($args) > 2) { $fileinfo['category_id']  = (int) $args[2]; }
        if (count($args) > 3) { $fileinfo['file_version'] = (int) $args[3]; }

        $this->file['fileinfo'] = $fileinfo;
    }
    
    private function parse_guid()
    {
        $args = $this->parse_args(2); // keyword + arg
        if ($args == null) { return; }
        
        $this->file['guid'] = $args[1];
    }
    
    private $file;
    private $line_number;
    private $cur_line;
    private $messages;
    private $cur_keyword;
}
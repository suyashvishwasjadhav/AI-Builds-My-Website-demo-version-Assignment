<?php

namespace App\Enums;

enum SectionType: string
{
    case Hero = 'hero';
    case About = 'about';
    case Services = 'services';
    case Gallery = 'gallery';
    case Contact = 'contact';
}

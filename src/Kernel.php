<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function boot(): void
    {
        // Les échéances et les jours ouvrés se calculent à l'heure de la Dordogne.
        date_default_timezone_set('Europe/Paris');

        parent::boot();
    }
}

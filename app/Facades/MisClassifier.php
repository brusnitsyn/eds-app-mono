<?php

namespace App\Facades;

use App\Data\Mis\Classifier\ClassifierDepartmentData;
use App\Data\Mis\Classifier\ClassifierDepartmentProfileData;
use App\Data\Mis\Classifier\ClassifierDepartmentTypeData;
use App\Data\Mis\Classifier\ClassifierLpuData;
use App\Data\Mis\Classifier\ClassifierPrvdData;
use App\Data\Mis\Classifier\ClassifierPrvsData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * @method static Collection|ClassifierLpuData getLpu(int|null $id = null, string|null $mcod = null, string|null $guid = null)
 * @method static Collection|ClassifierDepartmentData getDepartment(int|null $id = null, string|null $code = null, string|null $guid = null)
 * @method static Collection|ClassifierPrvdData getPrvd(int|null $id = null, string|null $code = null, string|null $guid = null)
 * @method static Collection|ClassifierPrvsData getPrvs(int|null $id = null, string|null $code = null, string|null $guid = null)
 * @method static Collection|ClassifierDepartmentTypeData getDepartmentType(int|null $id = null, string|null $code = null, string|null $guid = null)
 * @method static Collection|ClassifierDepartmentProfileData getDepartmentProfile(int|null $id = null, string|null $code = null)
 *
 * @see \App\Services\MisClassifierService
 */
class MisClassifier extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'misclassifier.facade';
    }
}

<?php declare(strict_types=1);
/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

namespace Elabftw\Services;

/**
 * Provide a League\Filesystem adapter for S3 buckets file uploads
 */

use Aws\Credentials\CredentialsInterface;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Elabftw\Models\Config;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;

class S3Adapter
{
    private const S3_VERSION = '2006-03-01';

    public function __construct(private Config $config, private CredentialsInterface $credentials)
    {
    }

    public function getAdapter(): FilesystemAdapter
    {
        return new AwsS3V3Adapter(
            // S3Client
            $this->getClient(),
            // Bucket name
            $this->config->configArr['s3_bucket_name'],
            // Optional path prefix
            $this->config->configArr['s3_path_prefix'],
            // Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
            new PortableVisibilityConverter(Visibility::PRIVATE),
        );
    }

    private function getClient(): S3ClientInterface
    {
        return new S3Client(array(
            'version' => self::S3_VERSION,
            'region' => $this->config->configArr['s3_region'],
            'endpoint' => $this->config->configArr['s3_endpoint'],
            'credentials' => $this->credentials,
        ));
    }
}

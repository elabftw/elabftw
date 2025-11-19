<?php

/**
 * @author Nicolas CARPi <nico-git@deltablot.email>
 * @copyright 2022 Nicolas CARPi
 * @see https://www.elabftw.net Official website
 * @license AGPL-3.0
 * @package elabftw
 */

declare(strict_types=1);

namespace Elabftw\Storage;

use Aws\Credentials\CredentialsInterface;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Elabftw\Elabftw\S3Config;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use League\Flysystem\AwsS3V3\PortableVisibilityConverter;
use League\Flysystem\FilesystemAdapter;
use League\Flysystem\Visibility;
use Override;

/**
 * Provide a League\Filesystem adapter for S3 buckets file uploads
 */
class S3 extends AbstractStorage
{
    protected const string S3_VERSION = '2006-03-01';

    // 100 Mb
    protected const int PART_SIZE = 104857600;

    public function __construct(
        protected readonly CredentialsInterface $credentials,
        protected readonly S3Config $config,
    ) {}

    #[Override]
    public function getPath(string $relativePath = ''): string
    {
        return $this->config->pathPrefix . ($relativePath !== '' ? '/' . $relativePath : '');
    }

    #[Override]
    public function getAbsoluteUri(string $path): string
    {
        // https://maennchen.dev/ZipStream-PHP/guide/StreamOutput.html#stream-to-s3-bucket
        return 's3://' . $this->config->bucketName . '/' . $this->getPath($path);
    }

    #[Override]
    protected function getAdapter(): FilesystemAdapter
    {
        $client = $this->getClient();
        // we register the wrapper here so we can do fopen() calls to s3
        $client->registerStreamWrapper();
        return new AwsS3V3Adapter(
            // S3Client
            $client,
            // Bucket name
            $this->config->bucketName,
            // Optional path prefix
            $this->config->pathPrefix,
            // Visibility converter (League\Flysystem\AwsS3V3\VisibilityConverter)
            new PortableVisibilityConverter(Visibility::PRIVATE),
            // set a larger part size for multipart upload or we hit the max number of parts (1000)
            options: array('part_size' => self::PART_SIZE),
        );
    }

    protected function getClient(): S3ClientInterface
    {
        return new S3Client(array(
            'version' => self::S3_VERSION,
            'region' => $this->config->region,
            'endpoint' => $this->config->endpoint,
            'credentials' => $this->credentials,
            'use_aws_shared_config_files' => false,
            'use_path_style_endpoint' => $this->config->usePathStyleEndpoint,
            'http' => array(
                'verify' => $this->config->verifyCert,
            ),
        ));
    }
}

#!/usr/bin/env php
<?php

const PROJECT_ROOT            = __DIR__;
const MANIFEST_FILE           = PROJECT_ROOT . '/shortcoder.xml';
const UPDATE_FILE             = PROJECT_ROOT . '/update.xml';
const GITHUB_REPO             = 'PopArtDesign/joomla-shortcoder';
const CLIENT_TYPE             = 'site';
const TARGET_PLATFORM_VERSION = '(4\.|5\.|6\.)';
const MIN_PHP_VERSION         = '7.4';

/**
 * Main function to orchestrate the update process.
 *
 * @param array $argv Command-line arguments.
 */
function main(array $argv): void
{
    $tempZipPath = ''; // Will be set after version is determined

    // 1. Argument Handling
    $version = parseArguments($argv);

    $pluginData = parseManifest(MANIFEST_FILE, $version);
    $pluginData['version'] = $version;
    $pluginData['phpMinimumVersion'] = MIN_PHP_VERSION;
    $pluginData['downloadUrl'] = buildDownloadUrl(GITHUB_REPO, $pluginData['version']);
    $pluginData['infoUrl'] = buildInfoUrl(GITHUB_REPO, $pluginData['version']);
    $pluginData['clientType'] = CLIENT_TYPE;
    $pluginData['targetPlatformVersion'] = TARGET_PLATFORM_VERSION;

    $tempZipPath = sys_get_temp_dir() . '/joomla-shortcoder-' . $pluginData['version'] . '.zip';

    try {
        downloadZipFile($pluginData['downloadUrl'], $tempZipPath);

        $pluginData['checksums'] = calculateChecksums($tempZipPath);

        updateUpdateXml(UPDATE_FILE, $pluginData);
    } finally {
        if (file_exists($tempZipPath)) {
            @unlink($tempZipPath);
            echo "Temporary ZIP file deleted.\n";
        }
    }
}

// Run the main function
main($argv);

/**
 * Exits the script with an error message.
 *
 * @param string $message The error message.
 */
function exitWithError(string $message): void
{
    fprintf(STDERR, "Error: %s\n", $message);

    exit(1);
}

/**
 * Parses command-line arguments and returns the version.
 *
 * @param array $argv The command-line arguments.
 * @return string|null The version string or null if not provided.
 */
function parseArguments(array $argv): ?string
{
    if (isset($argv[1])) {
        $version = $argv[1];
        if (!preg_match('/^\d+\.\d+\.\d+$/', $version)) {
            exitWithError("Invalid version format. Expected X.Y.Z");
        }
        return $version;
    }
    return null;
}

/**
 * Loads and parses shortcoder.xml, extracting plugin details.
 *
 * @param string      $filePath Path to Manifest.
 * @param string|null &$version Reference to version; updated if not provided by arguments.
 *
 * @return array Extracted plugin data.
 */
function parseManifest(string $filePath, ?string &$version): array
{
    $shortcoderXml = simplexml_load_file($filePath);
    if ($shortcoderXml === false) {
        exitWithError("Could not load " . basename($filePath));
    }

    $data = [
        'pluginName' => (string) $shortcoderXml->name,
        'pluginDescription' => (string) $shortcoderXml->description,
        'author' => (string) $shortcoderXml->author,
        'authorUrl' => (string) $shortcoderXml->authorUrl,
        'extensionType' => (string) $shortcoderXml['type'],
        'extensionGroup' => (string) $shortcoderXml['group'],
        'pluginElement' => '',
    ];

    // If version was not provided as an argument, get it from shortcoder.xml
    if ($version === null) {
        $version = (string)$shortcoderXml->version;
        if (empty($version)) {
            exitWithError("Version not specified and could not be read from " . basename($filePath));
        }
        echo "Using version from " . basename($filePath) . ": " . $version . "\n";
    }

    // Extract element from <files><folder plugin="shortcoder">
    foreach ($shortcoderXml->files->folder as $folder) {
        if (isset($folder['plugin'])) {
            $data['pluginElement'] = (string)$folder['plugin'];
            break;
        }
    }

    if (empty($data['pluginElement'])) {
        exitWithError("Could not determine plugin element from " . basename($filePath));
    }

    return $data;
}

/**
 * Constructs the download URL for the ZIP file.
 */
function buildDownloadUrl(string $githubRepo, string $version): string
{
    return sprintf(
        'https://github.com/%s/archive/refs/tags/v%s.zip',
        $githubRepo,
        $version
    );
}

/**
 * Constructs the info URL for the release.
 */
function buildInfoUrl(string $githubRepo, string $version): string
{
    return sprintf(
        'https://github.com/%s/releases/tag/v%s',
        $githubRepo,
        $version
    );
}

/**
 * Downloads a ZIP file and saves it to a temporary path.
 *
 * @param string $downloadUrl The URL to download from.
 * @param string $tempZipPath The path to save the downloaded file.
 */
function downloadZipFile(string $downloadUrl, string $tempZipPath): void
{
    echo "Attempting to download: " . $downloadUrl . "\n";

    $zipContent = @file_get_contents($downloadUrl);
    if ($zipContent === false) {
        exitWithError("Failed to download ZIP file from " . $downloadUrl);
    }

    if (file_put_contents($tempZipPath, $zipContent) === false) {
        exitWithError("Failed to save ZIP file to " . $tempZipPath);
    }

    echo "ZIP file downloaded successfully to " . $tempZipPath . "\n";
}

/**
 * Calculates SHA256, SHA384, and SHA512 checksums for a given file.
 *
 * @param string $filePath The path to the file.
 *
 * @return array An associative array of checksums.
 */
function calculateChecksums(string $filePath): array
{
    $sha256 = hash_file('sha256', $filePath);
    $sha384 = hash_file('sha384', $filePath);
    $sha512 = hash_file('sha512', $filePath);

    if ($sha256 === false || $sha384 === false || $sha512 === false) {
        exitWithError("Failed to calculate checksums for " . $filePath);
    }
    echo "Checksums calculated.
";
    return ['sha256' => $sha256, 'sha384' => $sha384, 'sha512' => $sha512];
}

/**
 * Updates the update.xml file with a new update entry.
 *
 * @param string $updateXmlPath Path to update.xml.
 * @param array  $data          All data required for the update entry.
 */
function updateUpdateXml(string $updateXmlPath, array $data): void
{
    $dom = new DOMDocument('1.0', 'utf-8');
    $dom->formatOutput = true;
    $dom->preserveWhiteSpace = false;

    if (file_exists($updateXmlPath)) {
        if (!$dom->load($updateXmlPath)) {
            exitWithError("Could not load existing " . basename($updateXmlPath));
        }
    } else {
        $updates = $dom->createElement('updates');
        $dom->appendChild($updates);
    }

    $updatesRoot = $dom->getElementsByTagName('updates')->item(0);
    if ($updatesRoot === null) {
        exitWithError("<updates> root element not found or created.");
    }

    // Remove existing update for this version if it exists
    $existingUpdateNode = null;
    foreach ($updatesRoot->getElementsByTagName('update') as $existingUpdate) {
        $existingVersion = $existingUpdate->getElementsByTagName('version')->item(0);
        if ($existingVersion && $existingVersion->nodeValue === $data['version']) {
            echo "Warning: Update for version " . $data['version'] . " already exists. Overwriting.\n";
            $existingUpdateNode = $existingUpdate;
            break;
        }
    }

    if ($existingUpdateNode) {
        $updatesRoot->removeChild($existingUpdateNode);
    }

    $updateNode = $dom->createElement('update');

    $updateNode->appendChild($dom->createElement('name', $data['pluginName']));
    $updateNode->appendChild($dom->createElement('description', $data['pluginDescription']));
    $updateNode->appendChild($dom->createElement('element', $data['pluginElement']));
    $updateNode->appendChild($dom->createElement('type', $data['extensionType']));
    $updateNode->appendChild($dom->createElement('folder', $data['extensionGroup']));
    $updateNode->appendChild($dom->createElement('client', $data['clientType']));
    $updateNode->appendChild($dom->createElement('maintainer', $data['author']));
    $updateNode->appendChild($dom->createElement('maintainerurl', $data['authorUrl']));
    $updateNode->appendChild($dom->createElement('version', $data['version']));

    $downloadsNode = $dom->createElement('downloads');
    $downloadUrlNode = $dom->createElement('downloadurl', $data['downloadUrl']);
    $downloadUrlNode->setAttribute('type', 'full');
    $downloadUrlNode->setAttribute('format', 'zip');
    $downloadsNode->appendChild($downloadUrlNode);
    $updateNode->appendChild($downloadsNode);

    $updateNode->appendChild($dom->createElement('sha512', $data['checksums']['sha512']));
    $updateNode->appendChild($dom->createElement('sha384', $data['checksums']['sha384']));
    $updateNode->appendChild($dom->createElement('sha256', $data['checksums']['sha256']));

    $infoUrlNode = $dom->createElement('infourl', $data['infoUrl']);
    $infoUrlNode->setAttribute('title', $data['pluginName']);
    $updateNode->appendChild($infoUrlNode);

    $targetPlatformNode = $dom->createElement('targetplatform');
    $targetPlatformNode->setAttribute('name', 'joomla');
    $targetPlatformNode->setAttribute('version', $data['targetPlatformVersion']);
    $updateNode->appendChild($targetPlatformNode);

    $updateNode->appendChild($dom->createElement('php_minimum', $data['phpMinimumVersion']));

    $updatesRoot->appendChild($updateNode);

    if (($xml = $dom->saveXML()) === false) {
        exitWithError('Failed to generate XML');
    }

    if (file_put_contents($updateXmlPath, $xml) === false) {
        exitWithError("Failed to save " . basename($updateXmlPath));
    }

    echo basename($updateXmlPath) . " updated successfully.\n";
}

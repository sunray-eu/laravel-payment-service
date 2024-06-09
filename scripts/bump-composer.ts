import * as fs from 'fs';

// Read the current version from composer.json
const json = JSON.parse(fs.readFileSync(import.meta.dirname + '/../composer.json', 'utf-8'));
const currentVersion: string = json['version'];

// Get the bump type from the command line argument
const bumpType: string = process.argv[2] || 'patch';

// Parse the current version
let [major, minor, patch] = currentVersion.split('.').map(Number);

// Increment version based on bump type
switch (bumpType) {
    case 'major':
        major++;
        minor = 0;
        patch = 0;
        break;
    case 'minor':
        minor++;
        patch = 0;
        break;
    case 'patch':
    default:
        patch++;
}

// Update the version in composer.json
const newVersion: string = `${major}.${minor}.${patch}`;
json['version'] = newVersion;
fs.writeFileSync(import.meta.dirname + '/../composer.json', JSON.stringify(json, null, 4));

// Output the new version
console.log(`Version bumped to ${newVersion}`);

#!/usr/bin/env node

const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');
const chalk = require('chalk');

class BuildAndDeploy {
    constructor() {
        this.projectDir = process.cwd();
        this.version = this.getVersion();
    }

    log(message, type = 'info') {
        const timestamp = new Date().toLocaleTimeString();
        const colors = {
            info: chalk.blue,
            success: chalk.green,
            error: chalk.red,
            warning: chalk.yellow
        };

        console.log(`${chalk.gray(timestamp)} ${colors[type]('●')} ${message}`);
    }

    getVersion() {
        try {
            const packageJson = JSON.parse(fs.readFileSync(path.join(this.projectDir, 'package.json'), 'utf8'));
            return packageJson.version;
        } catch (error) {
            return '1.0.0';
        }
    }

    async buildPlugin() {
        this.log('Building plugin package...', 'info');

        try {
            execSync('npm run build:zip', { stdio: 'inherit' });
            this.log('Plugin build completed successfully', 'success');
            return true;
        } catch (error) {
            this.log('Plugin build failed: ' + error.message, 'error');
            return false;
        }
    }

    async deployToDistBranch() {
        this.log('Deploying to dist branch...', 'info');

        try {
            // Check if we have uncommitted changes
            const status = execSync('git status --porcelain', { encoding: 'utf8' });
            if (status.trim()) {
                this.log('Warning: You have uncommitted changes', 'warning');
            }

            // Get current branch
            const currentBranch = execSync('git rev-parse --abbrev-ref HEAD', { encoding: 'utf8' }).trim();

            // Switch to dist branch
            try {
                execSync('git checkout dist', { stdio: 'pipe' });
            } catch (error) {
                // Create dist branch if it doesn't exist
                execSync('git checkout -b dist', { stdio: 'pipe' });
            }

            // Copy built package
            const builtPackage = `dist/amfm-tools-${this.version}.zip`;
            if (!fs.existsSync(builtPackage)) {
                throw new Error(`Built package not found: ${builtPackage}`);
            }

            execSync(`cp "${builtPackage}" amfm-tools-latest.zip`);

            // Commit and push
            execSync('git add amfm-tools-latest.zip');
            execSync(`git commit -m "Deploy built package v${this.version}" || true`);
            execSync('git push origin dist');

            // Switch back to original branch
            execSync(`git checkout ${currentBranch}`);

            this.log(`Deployed v${this.version} to dist branch`, 'success');
            return true;
        } catch (error) {
            this.log('Deploy failed: ' + error.message, 'error');
            return false;
        }
    }

    async run() {
        this.log(`Starting build and deploy for v${this.version}`, 'info');

        const buildSuccess = await this.buildPlugin();
        if (!buildSuccess) {
            process.exit(1);
        }

        const deploySuccess = await this.deployToDistBranch();
        if (!deploySuccess) {
            process.exit(1);
        }

        this.log('Build and deploy completed successfully!', 'success');
        this.log(`Built package available at: https://github.com/adzadzadz/amfm-tools/raw/dist/amfm-tools-latest.zip`, 'info');
    }
}

// Run if called directly
if (require.main === module) {
    const deployer = new BuildAndDeploy();
    deployer.run().catch(console.error);
}

module.exports = BuildAndDeploy;
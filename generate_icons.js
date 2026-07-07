import sharp from 'sharp';
import fs from 'fs';
import path from 'path';

const svgPath = 'public/logo.svg';
const androidResPath = 'nativephp/android/app/src/main/res';

async function generate() {
    const densities = {
        'mdpi': 48,
        'hdpi': 72,
        'xhdpi': 96,
        'xxhdpi': 144,
        'xxxhdpi': 192,
    };

    console.log('Generating launcher icons...');
    for (const [density, size] of Object.entries(densities)) {
        const outDir = path.join(androidResPath, `mipmap-${density}`);
        if (!fs.existsSync(outDir)) {
            fs.mkdirSync(outDir, { recursive: true });
        }
        
        const launcherSize = size;
        
        await sharp(svgPath)
            .resize(launcherSize, launcherSize)
            .webp()
            .toFile(path.join(outDir, 'ic_launcher.webp'));
            
        await sharp(svgPath)
            .resize(launcherSize, launcherSize)
            .webp()
            .toFile(path.join(outDir, 'ic_launcher_round.webp'));
            
        await sharp(svgPath)
            .resize(launcherSize, launcherSize)
            .webp()
            .toFile(path.join(outDir, 'ic_launcher_foreground.webp'));
            
        console.log(`Generated ${density} icons.`);
    }

    console.log('Generating splash screen...');
    const drawableDir = path.join(androidResPath, 'drawable');
    if (!fs.existsSync(drawableDir)) fs.mkdirSync(drawableDir, { recursive: true });
    
    // Create a 1080x1920 background with color #f3fbf2
    // Composite the logo (resized to 500x500) in the center
    await sharp({
        create: {
            width: 1080,
            height: 1920,
            channels: 4,
            background: '#f3fbf2'
        }
    })
    .composite([
        {
            input: await sharp(svgPath).resize(500, 500).toBuffer(),
            gravity: 'center'
        }
    ])
    .png()
    .toFile(path.join(drawableDir, 'splash.png'));
    
    console.log('Generated splash screen.');
    console.log('All done!');
}

generate().catch(console.error);

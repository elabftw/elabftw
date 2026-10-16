// Full-page live reload for development builds (no HMR or separate dev server).
let previousBuild: string | undefined;

async function checkBuild(): Promise<void> {
  try {
    const response = await fetch('/assets/dev-reload.txt', { cache: 'no-store' });
    if (response.ok) {
      const currentBuild = (await response.text()).trim();
      if (currentBuild) {
        if (previousBuild !== undefined && currentBuild !== previousBuild) {
          window.location.reload();
          return;
        }
        previousBuild = currentBuild;
      }
    }
  } catch {
    // Keep polling if the development server is temporarily unavailable.
  }
  window.setTimeout(() => void checkBuild(), 1000);
}

void checkBuild();

export {};

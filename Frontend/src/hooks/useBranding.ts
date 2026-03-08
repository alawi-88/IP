// hooks/useBranding.ts
import { useState, useEffect } from 'react';

interface BrandingSettings {
  primary_color: string;
  secondary_color: string;
  font: string;
  logo: string | null;
  white_logo: string | null;
  favicon: string | null;
  email_bg_color: string;
  email_text_color: string;
  email_link_color: string;
  email_border_color: string;
  email_footer: string;
  email_logo: string | null;
  email_footer_footer: string | null;
}

const DEFAULT_BRANDING: BrandingSettings = {
  primary_color: '#25935F',
  secondary_color: '#1a6b44',
  font: 'IBM Plex Sans',
  logo: null,
  white_logo: null,
  favicon: null,
  email_bg_color: '#FFFFFF',
  email_text_color: '#111827',
  email_link_color: '#1E40AF',
  email_border_color: '#E5E7EB',
  email_footer: '',
  email_logo: null,
  email_footer_footer: null,
};

let cachedBranding: BrandingSettings | null = null;

export function useBranding() {
  const [branding, setBranding] = useState<BrandingSettings>(cachedBranding || DEFAULT_BRANDING);
  const [loading, setLoading] = useState(!cachedBranding);

  useEffect(() => {
    if (cachedBranding) return;
    const fetchBranding = async () => {
      try {
        const apiUrl = process.env.NEXT_PUBLIC_API_URL || '';
        const response = await fetch(`${apiUrl}/api/branding`);
        if (response.ok) {
          const result = await response.json();
          if (result.success && result.data) {
            cachedBranding = result.data;
            setBranding(result.data);
            applyBrandingToDOM(result.data);
          }
        }
      } catch (error) {
        console.warn('Failed to fetch branding settings, using defaults');
      } finally {
        setLoading(false);
      }
    };
    fetchBranding();
  }, []);

  return { branding, loading };
}

export function applyBrandingToDOM(branding: BrandingSettings) {
  const root = document.documentElement;
  const hexToRgb = (hex: string): string => {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    if (!result) return '37, 147, 95';
    return `${parseInt(result[1], 16)}, ${parseInt(result[2], 16)}, ${parseInt(result[3], 16)}`;
  };
  const adjustColor = (hex: string, percent: number): string => {
    const num = parseInt(hex.replace('#', ''), 16);
    const amt = Math.round(2.55 * percent);
    const R = Math.min(255, Math.max(0, (num >> 16) + amt));
    const G = Math.min(255, Math.max(0, (num >> 8 & 0x00FF) + amt));
    const B = Math.min(255, Math.max(0, (num & 0x0000FF) + amt));
    return `#${(0x1000000 + R * 0x10000 + G * 0x100 + B).toString(16).slice(1)}`;
  };
  const primary = branding.primary_color || '#25935F';
  const secondary = branding.secondary_color || '#1a6b44';
  // DGA design system variables
  root.style.setProperty('--dga-primary-500', primary);
  root.style.setProperty('--dga-primary-600', secondary);
  root.style.setProperty('--dga-primary-400', adjustColor(primary, 20));
  root.style.setProperty('--dga-primary-300', adjustColor(primary, 40));
  root.style.setProperty('--dga-primary-200', adjustColor(primary, 60));
  root.style.setProperty('--dga-primary-100', adjustColor(primary, 80));
  root.style.setProperty('--dga-primary-50', adjustColor(primary, 90));
  root.style.setProperty('--dga-primary-700', adjustColor(primary, -20));
  root.style.setProperty('--dga-primary-800', adjustColor(primary, -40));
  root.style.setProperty('--dga-primary-900', adjustColor(primary, -60));
  root.style.setProperty('--dga-primary-rgb', hexToRgb(primary));
  // Sync Tailwind CSS variables so text-primary, bg-primary etc. reflect branding
  root.style.setProperty('--primary-color', primary);
  root.style.setProperty('--secondary-color', secondary);
  if (branding.font) {
    root.style.setProperty('--dga-font', `"${branding.font}", "Noto Sans Arabic", sans-serif`);
    root.style.setProperty('--font', branding.font);
  }
  if (branding.favicon) {
    const existingFavicon = document.querySelector('link[rel="icon"]') as HTMLLinkElement;
    if (existingFavicon) { existingFavicon.href = branding.favicon; }
  }
}

export default useBranding;

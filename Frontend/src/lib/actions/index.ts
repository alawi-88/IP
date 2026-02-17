import { Theme } from "@/lib/interfaces";

// Use internal Docker URL for server-side calls, public URL for client-side
const getApiEndpoint = () => {
  if (typeof window === "undefined") {
    // Server-side: prefer internal Docker network URL
    return (
      process.env.INTERNAL_API_ENDPOINT ||
      process.env.NEXT_PUBLIC_API_ENDPOINT
    );
  }
  // Client-side: use public URL
  return process.env.NEXT_PUBLIC_API_ENDPOINT;
};

//getBrandTheme
export async function getBrandTheme(): Promise<Theme | null> {
  try {
    const res = await fetch(`${getApiEndpoint()}/branding-settings`, {
      cache: "no-store",
    });

    if (!res.ok) {
      console.warn("Failed to fetch theme:", res.status);
      return null;
    }

    return (await res.json()) as Theme;
  } catch (error) {
    console.warn("Failed to fetch theme:", error);
    return null;
  }
}

//geLandingPage
export async function getLandingPage(locale: string) {
  try {
    const res = await fetch(`${getApiEndpoint()}/landing-page`, {
      cache: "no-store",
      headers: {
        "Accept-Language": locale,
      },
    });

    if (!res.ok) {
      console.warn("Failed to fetch landing page:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.warn("Failed to fetch landing page:", error);
    return null;
  }
}

//getServices
export async function getServices(locale: string) {
  try {
    const res = await fetch(`${getApiEndpoint()}/services`, {
      cache: "no-store",
      headers: {
        "Accept-Language": locale,
      },
    });

    if (!res.ok) {
      console.warn("Failed to fetch services:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.warn("Failed to fetch services:", error);
    return null;
  }
}

//getSingleService
export async function getSingleService(locale: string, id: string) {
  try {
    const res = await fetch(`${getApiEndpoint()}/services/${id}`, {
      cache: "no-store",
      headers: {
        "Accept-Language": locale,
      },
    });

    if (!res.ok) {
      console.warn("Failed to fetch service:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.warn("Failed to fetch service:", error);
    return null;
  }
}

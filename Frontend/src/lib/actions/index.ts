import { Theme } from "@/lib/interfaces";

//getBrandTheme
export async function getBrandTheme(): Promise<Theme | null> {
  try {
    const res = await fetch(
      `${process.env.NEXT_PUBLIC_API_ENDPOINT}/branding-settings`,
      {
        cache: "no-store",
      }
    );

    if (!res.ok) {
      console.error("Failed to fetch theme:", res.status);
      return null;
    }

    return (await res.json()) as Theme;
  } catch (error) {
    console.error("Failed to fetch theme:", error);
    return null;
  }
}

//geLandingPage
export async function getLandingPage(locale: string) {
  try {
    const res = await fetch(
      `${process.env.NEXT_PUBLIC_API_ENDPOINT}/landing-page`,
      {
        cache: "no-store",
        headers: {
          "Accept-Language": locale,
        },
      }
    );

    if (!res.ok) {
      console.error("Failed to fetch landing page:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.error("Failed to fetch landing page:", error);
    return null;
  }
}

//getServices
export async function getServices(locale: string) {
  try {
    const res = await fetch(
      `${process.env.NEXT_PUBLIC_API_ENDPOINT}/services`,
      {
        cache: "no-store",
        headers: {
          "Accept-Language": locale,
        },
      }
    );

    if (!res.ok) {
      console.error("Failed to fetch landing page:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.error("Failed to fetch landing page:", error);
    return null;
  }
}

//getSingleService
export async function getSingleService(locale: string, id: string) {
  try {
    const res = await fetch(
      `${process.env.NEXT_PUBLIC_API_ENDPOINT}/services/${id}`,
      {
        cache: "no-store",
        headers: {
          "Accept-Language": locale,
        },
      }
    );

    if (!res.ok) {
      console.error("Failed to fetch landing page:", res.status);
      return null;
    }

    return await res.json();
  } catch (error) {
    console.error("Failed to fetch landing page:", error);
    return null;
  }
}

import { FormInstance } from "antd";
import { APIError } from "@/axios";

const customKey = (key: string) => {
  // Handle array-like keys (e.g., "serial_numbers.0" -> "serial_numbers")
  if (typeof key === "string" && key.includes(".")) {
    return key.split(".")[0];
  }
  return key;
};
const useSetFieldsErrors =
  (form: FormInstance, ck = customKey) =>
  (error: Error | APIError | null) => {
    if (error) {
      // @ts-expect-error - This is a custom key function
      const fields = error?.response?.data?.errors;
      if (fields) {
        // Group errors by their base field name
        const groupedErrors = Object.entries(fields).reduce(
          (acc, [key, errors]) => {
            const baseKey = ck(key);
            if (!acc[baseKey]) {
              acc[baseKey] = [];
            }
            acc[baseKey].push(...(Array.isArray(errors) ? errors : [errors]));
            return acc;
          },
          {} as Record<string, string[]>
        );

        form.setFields(
          Object.entries(groupedErrors).map(([key, errors]) => ({
            name: key,
            errors: errors.filter(Boolean),
          }))
        );
      }
    }
  };

export default useSetFieldsErrors;

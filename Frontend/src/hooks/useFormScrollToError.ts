import { FormInstance } from "antd";

export const useFormScrollToError = (form: FormInstance) => {
  return () => {
    const errorFields = form
      .getFieldsError()
      .filter(({ errors }) => errors.length > 0);

    if (errorFields.length > 0) {
      const staticFields = document.querySelectorAll(".static_field");
      const staticErrorEl = Array.from(staticFields)
        .find((field) => field.classList.contains("ant-form-item-has-error"))
        ?.querySelector("[id][aria-invalid='true']");

      const firstError =
        staticErrorEl?.getAttribute("id") || errorFields[0].name[0];



      if (firstError) {
        form.scrollToField(firstError, {
          behavior: "smooth",
          block: "center",
        });
      }
    }
  };
};

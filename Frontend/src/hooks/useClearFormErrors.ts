import { FormInstance } from 'antd';

export const useClearFormErrors = (form: FormInstance) => {
  return () => {
    const fieldsWithErrors = form
      .getFieldsError()
      .filter(({ errors }) => errors.length > 0);

    const cleared = fieldsWithErrors.map(({ name }) => ({
      name,
      errors: [],
    }));

    form.setFields(cleared);
  };
};
